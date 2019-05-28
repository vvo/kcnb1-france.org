@extends('layouts.app')
@section('content')
  <div class="container mb-6">
    @include('partials.page-header')

    @if (!have_posts())
      <div class="alert alert-warning">
        {{ __('Sorry, no results were found.', 'sage') }}
      </div>
      {!! get_search_form(false) !!}
    @endif

    @php
      $count = 0;
    @endphp
    @while (have_posts()) @php the_post() @endphp
      @include('partials.content-'.get_post_type())
      @php
      $count++;
    @endphp
    @endwhile

    {!! get_the_posts_navigation() !!}
  </div>
@endsection
