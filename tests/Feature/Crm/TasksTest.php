<?php

use App\Domain\Crm\Actions\SendTaskDigest;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Task;
use App\Domain\Crm\Models\TeamMember;
use App\Domain\Crm\Notifications\TaskDigest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->member = TeamMember::factory()->create();
    $this->actingAs($this->member->user);
    $this->company = Company::factory()->create();
    // Lunes 9 de noviembre de 2026, 10:00 hora de la Ciudad de México.
    $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00', 'America/Mexico_City'));
});

function companyPageFor(Company $company)
{
    return Livewire::test('pages::crm.company', ['company' => $company]);
}

test('a task is overdue only after its day has passed in the business time zone', function () {
    $task = Task::factory()->create(['due_on' => '2026-11-09']);

    $this->travelTo(CarbonImmutable::parse('2026-11-09 23:30', 'America/Mexico_City'));
    expect($task->isOverdue())->toBeFalse()
        ->and(Task::overdue()->count())->toBe(0)
        ->and(Task::dueByToday()->count())->toBe(1);

    $this->travelTo(CarbonImmutable::parse('2026-11-10 00:30', 'America/Mexico_City'));
    expect($task->isOverdue())->toBeTrue()
        ->and(Task::overdue()->count())->toBe(1);
});

test('completed tasks and tasks without a date are never overdue', function () {
    Task::factory()->completed()->create(['due_on' => '2026-11-01']);
    Task::factory()->create(['due_on' => null]);

    expect(Task::overdue()->count())->toBe(0)->and(Task::dueByToday()->count())->toBe(0);
});

test('a task is added to a company, assigned to the current user by default', function () {
    companyPageFor($this->company)
        ->assertSet('taskAssignee', $this->member->user_id)
        ->set('taskTitle', 'Llamar para dar seguimiento')
        ->set('taskDueOn', '2026-11-12')
        ->call('addTask')
        ->assertHasNoErrors()
        ->assertSet('taskTitle', '');

    $task = $this->company->tasks()->sole();

    expect($task->title)->toBe('Llamar para dar seguimiento')
        ->and($task->due_on->toDateString())->toBe('2026-11-12')
        ->and($task->assigned_to)->toBe($this->member->user_id);
});

test('a task may have no due date and no assignee', function () {
    companyPageFor($this->company)->set('taskTitle', 'Pendiente')->set('taskAssignee', null)->call('addTask')->assertHasNoErrors();

    expect($this->company->tasks()->sole()->due_on)->toBeNull()
        ->and($this->company->tasks()->sole()->assigned_to)->toBeNull();
});

test('a task needs a title and an assignee from the crm team', function () {
    companyPageFor($this->company)->set('taskTitle', '')->call('addTask')->assertHasErrors(['taskTitle' => 'required']);

    companyPageFor($this->company)
        ->set('taskTitle', 'Algo')
        ->set('taskAssignee', User::factory()->create()->id)
        ->call('addTask')
        ->assertHasErrors('taskAssignee');

    expect(Task::count())->toBe(0);
});

test('tasks can be completed, reopened and deleted from the company page', function () {
    $task = Task::factory()->for($this->company)->create();
    $page = companyPageFor($this->company);

    $page->call('completeTask', $task->id);
    expect($task->fresh()->completed_at)->not->toBeNull();

    $page->call('reopenTask', $task->id);
    expect($task->fresh()->completed_at)->toBeNull();

    $page->call('deleteTask', $task->id);
    expect(Task::count())->toBe(0);
});

test('tasks of another company cannot be touched from this page', function () {
    $foreign = Task::factory()->create();

    expect(fn () => companyPageFor($this->company)->call('completeTask', $foreign->id))->toThrow(ModelNotFoundException::class);
});

test('overdue tasks are flagged on the company page and on the pipeline card', function () {
    Task::factory()->for($this->company)->create(['title' => 'Mandar propuesta', 'due_on' => '2026-11-05']);
    Task::factory()->for($this->company)->create(['due_on' => '2026-11-20']);
    Task::factory()->for($this->company)->completed()->create(['due_on' => '2026-11-01']);

    $this->get(route('crm.companies.show', $this->company))->assertSee('Mandar propuesta')->assertSee('Vencida');
    $this->get(route('crm.pipeline'))->assertSee('1 tarea vencida');
});

test('an activity can be logged with a past date given in the business time zone', function () {
    companyPageFor($this->company)
        ->set('activityBody', 'Llamada con la directora')
        ->set('activityAt', '2026-11-02T09:30')
        ->call('addActivity')
        ->assertHasNoErrors()
        ->assertSet('activityAt', '');

    expect($this->company->activities()->sole()->occurred_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-02 15:30');
});

test('an activity without a date happens now and one in the future is rejected', function () {
    companyPageFor($this->company)->set('activityBody', 'Nota')->call('addActivity')->assertHasNoErrors();

    expect($this->company->activities()->sole()->occurred_at->equalTo(now()))->toBeTrue();

    companyPageFor($this->company)
        ->set('activityBody', 'Del futuro')
        ->set('activityAt', '2026-11-20T09:00')
        ->call('addActivity')
        ->assertHasErrors('activityAt');

    expect($this->company->activities()->count())->toBe(1);
});

describe('tasks page', function () {
    test('is closed to users outside the crm team', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('crm.tasks'))->assertForbidden();
    });

    test('groups my pending tasks by urgency and hides what is completed or someone else\'s', function () {
        $mine = $this->member->user_id;
        Task::factory()->create(['title' => 'Vencida mía', 'due_on' => '2026-11-03', 'assigned_to' => $mine]);
        Task::factory()->create(['title' => 'De hoy mía', 'due_on' => '2026-11-09', 'assigned_to' => $mine]);
        Task::factory()->create(['title' => 'Próxima mía', 'due_on' => '2026-11-15', 'assigned_to' => $mine]);
        Task::factory()->create(['title' => 'Sin fecha mía', 'due_on' => null, 'assigned_to' => $mine]);
        Task::factory()->completed()->create(['title' => 'Hecha mía', 'assigned_to' => $mine]);
        Task::factory()->create(['title' => 'De otra persona', 'assigned_to' => TeamMember::factory()->create()->user_id]);

        $groups = Livewire::test('pages::crm.tasks')->assertDontSee('Hecha mía')->assertDontSee('De otra persona')->instance()->groups;

        expect($groups['Vencidas']->pluck('title')->all())->toBe(['Vencida mía'])
            ->and($groups['Hoy']->pluck('title')->all())->toBe(['De hoy mía'])
            ->and($groups['Próximas']->pluck('title')->all())->toBe(['Próxima mía'])
            ->and($groups['Sin fecha']->pluck('title')->all())->toBe(['Sin fecha mía']);
    });

    test('can show everyone\'s tasks', function () {
        Task::factory()->create(['title' => 'De otra persona', 'assigned_to' => TeamMember::factory()->create()->user_id]);

        Livewire::test('pages::crm.tasks')->assertDontSee('De otra persona')->set('scope', 'all')->assertSee('De otra persona');
    });

    test('completes a task', function () {
        $task = Task::factory()->create(['assigned_to' => $this->member->user_id]);

        Livewire::test('pages::crm.tasks')->call('complete', $task->id);

        expect($task->fresh()->completed_at)->not->toBeNull();
    });

    test('says so when there is nothing pending', function () {
        Livewire::test('pages::crm.tasks')->assertSee('No hay tareas pendientes');
    });
});

describe('daily digest', function () {
    test('notifies each team member with tasks due today or overdue, once, with only those tasks', function () {
        Notification::fake();
        $other = TeamMember::factory()->create();
        Task::factory()->create(['title' => 'Vencida', 'due_on' => '2026-11-04', 'assigned_to' => $this->member->user_id]);
        Task::factory()->create(['title' => 'De hoy', 'due_on' => '2026-11-09', 'assigned_to' => $this->member->user_id]);
        Task::factory()->create(['title' => 'Futura', 'due_on' => '2026-11-12', 'assigned_to' => $this->member->user_id]);
        Task::factory()->completed()->create(['title' => 'Hecha', 'due_on' => '2026-11-04', 'assigned_to' => $this->member->user_id]);
        Task::factory()->create(['title' => 'De otra', 'due_on' => '2026-11-09', 'assigned_to' => $other->user_id]);

        expect(app(SendTaskDigest::class)->handle())->toBe(2);

        Notification::assertSentToTimes($this->member->user, TaskDigest::class, 1);
        Notification::assertSentTo($this->member->user, TaskDigest::class, fn (TaskDigest $digest) => $digest->tasks->pluck('title')->all() === ['Vencida', 'De hoy']);
        Notification::assertSentTo($other->user, TaskDigest::class, fn (TaskDigest $digest) => $digest->tasks->pluck('title')->all() === ['De otra']);
    });

    test('sends nothing when nobody has tasks due, or to tasks without an assignee or outside the team', function () {
        Notification::fake();
        Task::factory()->create(['due_on' => '2026-11-12', 'assigned_to' => $this->member->user_id]);
        Task::factory()->create(['due_on' => '2026-11-09', 'assigned_to' => null]);
        Task::factory()->create(['due_on' => '2026-11-09', 'assigned_to' => User::factory()->create()->id]);

        expect(app(SendTaskDigest::class)->handle())->toBe(0);

        Notification::assertNothingSent();
    });

    test('the email lists the tasks and counts the overdue ones in the subject', function () {
        $overdue = Task::factory()->for(Company::factory()->state(['name' => 'Acme Ventas']), 'company')->create(['title' => 'Mandar propuesta', 'due_on' => '2026-11-04', 'assigned_to' => $this->member->user_id]);
        $today = Task::factory()->create(['title' => 'Llamar', 'due_on' => '2026-11-09', 'assigned_to' => $this->member->user_id]);

        $mail = (new TaskDigest(collect([$overdue->load('company'), $today->load('company')])))->toMail($this->member->user);
        $text = collect($mail->introLines)->implode("\n");

        expect($mail->subject)->toBe('Tienes 2 tareas del CRM para hoy (1 vencida)')
            ->and($text)->toContain('Acme Ventas: Mandar propuesta (vencida el 04/11/2026)')
            ->and($text)->toContain('Llamar (para hoy)')
            ->and($mail->actionUrl)->toBe(route('crm.tasks'));
    });

    test('the scheduled command runs the digest', function () {
        Notification::fake();
        Task::factory()->create(['due_on' => '2026-11-09', 'assigned_to' => $this->member->user_id]);

        $this->artisan('crm:send-task-digest')->expectsOutputToContain('Personas avisadas: 1')->assertSuccessful();

        Notification::assertSentTo($this->member->user, TaskDigest::class);
    });
});
