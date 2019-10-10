<div class="card-container mb-3 d-flex align-items-stretch">
  <div class="card">
    <div class="card-body">
      <div class="icon-text"><i class="far fa-comment fa-2x"></i> L'histoire de :</div>
      <h3 class="card-title">{{ the_title() }}</h3>
    </div>
    {{ the_post_thumbnail('card', ['class' => 'img-fluid']) }}
    <div class="card-body h124">
      <p class="card-text">{!! wp_trim_words(get_the_content(), 18) !!}</p>
    </div>
    <a href="{{ the_permalink() }}" title="{{ the_title() }}" class="btn btn-white btn-white-temoignages stretched-link">Lire le témoignage <i
        class="fas fa-stream fa-lg"></i></a>
  </div>
</div>
