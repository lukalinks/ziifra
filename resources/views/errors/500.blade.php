@extends('errors::layout')

@section('title', 'Server Error')
@section('code', '500')
@section('message', 'Something went wrong')
@section('detail', 'We hit an unexpected error. Please try again in a moment.')
