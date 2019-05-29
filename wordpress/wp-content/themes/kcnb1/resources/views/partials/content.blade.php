<article @php post_class() @endphp>
  <div class="entry-summary mt-5">
    <div class="row">
      @if($count % 2 === 0)
        <div class="offset-md-1 col-md-3 align-self-center">
      @else
        <div class="col-md-3 order-last align-self-center">
      @endif
        <a href="{{ get_permalink() }}">{{ the_post_thumbnail('article') }}</a>
      </div>
      @if($count % 2 === 0)
        <div class="col-md-3">
      @else
        <div class="offset-md-1 col-md-3">
      @endif
        @if(in_category('Évènement'))
        <div class="icon-text"><i class="far fa-calendar-alt fa-2x"></i> Évènement, publié le <time class="updated" datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time></div>
        @else
        <div class="icon-text"><i class="far fa-newspaper fa-2x"></i> Article, publié le <time class="updated" datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time></div>
        @endif
        <h3 class="mt-3"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h3>
        <p>{!! wp_trim_words(get_the_content(), in_category('Évènement') ? 30 : 70) !!}</p>
        <p><a href="{{ get_permalink() }}">Lire la suite →</a></p>
        @if(in_category('Évènement'))
        <div class="icon-text text-blue"><i class="far fa-sticky-note fa-lg"></i>Date de l'événement : {{ the_field('date') }}</div>
        <div class="icon-text text-blue"><i class="fas fa-map-marker-alt fa-lg"></i>Lieu : {{ get_field('lieu')['address'] }}</div>
        @endif
      </div>
    </div>
  </div>
</article>
