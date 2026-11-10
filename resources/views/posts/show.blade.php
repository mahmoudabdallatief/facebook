@extends('layouts.app')

@section('title', $post->user->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <x-post :post="$post" />
</div>