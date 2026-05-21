<?php
$content = <<<'BLADE'
@extends('layouts.app')

@section('title', 'Billing')
@section('header', 'Billing & plan')

@section('content')
<div class="max-w-3xl" id="plans">
    <p class="text-sm text-ziifra-muted">
        Manage your ZIIFRA subscription for <strong>{{ $organization->name }}</strong>.
    </p>

    @if ($stripeEnabled)
        <motion.div @class([
            'mt-4 rounded-lg border px-4 py-3 text-sm',
            $stripeCheckoutReady
                ? 'border-green-200 bg-green-50 text-green-900'
                : 'border-amber-200 bg-amber-50 text-amber-950',
        ])>
BLADE;
$content = str_replace('motion.div', 'div', $content);
file_put_contents('c:/Users/Dell/Desktop/ai/hr/resources/views/app/settings/billing.blade.php', $content);
