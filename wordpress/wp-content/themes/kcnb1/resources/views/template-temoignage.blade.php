{{--
  Template Name: Témoignage
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.content-temoignage')
    @include('partials.chiffres')
  @endwhile
@endsection
