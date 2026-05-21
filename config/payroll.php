<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kosovo payroll rules (monthly)
    |--------------------------------------------------------------------------
    | Pension: employee and employer each pay 5% of gross salary (common Kosovo
    | contributory withholding pattern in commercial payroll tools). Confirm
    | against ATK guidance for your contracts.
    |
    | Personal income tax (salary income): PwC Tax Summaries describes annual
    | bands — 0% on income up to €3,000; 8% on €3,000.01–€5,400; 10% above
    | €5,400 (amounts rounded to two decimals). For monthly payroll we model
    | the same progression by dividing those band widths by 12:
    |   €3,000 / 12 = €250/month at 0%
    |   (€5,400 − €3,000) / 12 = €200/month at 8% (cumulative ceiling €450/month)
    |   Remaining monthly taxable income at 10%
    |
    | Sources (verify before production use; law and guidance can change):
    | - https://taxsummaries.pwc.com/kosovo/individual/taxes-on-personal-income
    |
    | Tax is applied here to (gross salary minus employee pension). That
    | ordering is an implementation choice — validate against official
    | withholding instructions for your payroll.
    */

    'kosovo' => [
        'employee_pension_rate' => 0.05,
        'employer_pension_rate' => 0.05,
        'monthly_tax_brackets' => [
            ['up_to' => 250.00, 'rate' => 0.00],
            ['up_to' => 450.00, 'rate' => 0.08],
            ['up_to' => null, 'rate' => 0.10],
        ],
    ],

];
