@extends('layouts.app')

@section('content')
@while(have_posts()) @php the_post() @endphp
<div class="bg-gray mt-8 pb-1">
  <div class="container article-container">
    {{ the_post_thumbnail('full', ['class' => 'img-fluid']) }}
    <h1 class="mt-5 text-center">Merci pour votre don</h1>
    <div class="mt-8">
      <p>
        Votre don sera entièrement reversé à l'<a href="https://www.institutimagine.org/fr/">institut Imagine</a> pour la recherche sur la mutation du gène KCNB1.
      </p>
      <p>
        Vous allez recevoir un email récapitulatif de votre don, vous pouvez répondre à cet email quand bon vous semble pour nous poser des questions sur votre don.
      </p>
      <p>
        Merci encore et n'hésitez pas à inviter vos proches et connaissances à <a href="{{ get_permalink(141) }}">faire un don</a> à l'association KCNB1 en leur partageant notre page.
      </p>
    </div>
  </div>
</div>
@endwhile
@endsection
