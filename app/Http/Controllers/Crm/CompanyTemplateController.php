<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Services\CompanyCsv;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyTemplateController extends Controller
{
    public function __invoke(CompanyCsv $companyCsv): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print ($companyCsv->template()),
            'plantilla-empresas.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
