<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Services\ContactCsv;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactTemplateController extends Controller
{
    public function __invoke(ContactCsv $contactCsv): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print ($contactCsv->template()),
            'plantilla-contactos.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
