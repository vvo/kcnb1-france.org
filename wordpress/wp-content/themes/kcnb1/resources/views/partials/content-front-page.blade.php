@php the_content()
@endphp {!! wp_link_pages(['echo' => 0, 'before' => '
<nav class="page-nav">
  <p>' . __('Pages:', 'sage'), 'after' => '</p>
</nav>']) !!}

<div class="container">
  <div class="row no-gutters">
    <div class="col offset-md-1">
      <img src="@asset('images/front-page/visuel.jpg')" alt="Photo de Soline a 8 ans" width="433" height="661" />
    </div>
    <div class="col pt-4">
      <h1>La mutation du gène KCNB1 est une maladie génétique rare.</h1>
      <p class="mt-4">Nous nous appelons Candice, Juliette, Laura, Maïa, Sarah, Soline, Arthur, Léonard, Mathéo.L, Vincent et Mathéo LB.
        Nous avons été diagnostiqués avec une mutation du gène KCNB1. D’autres enfants souffrants d’épilepsie ont peut-être
        aussi cette mutation.
      </p>
      <a href="test" class="btn btn-red btn-lg mt-2">Faire un don à l'association</a>
      <p class="mt-4">
        <a href="test">Comprendre le gène KCNB1 et sa mutation →</a>
      </p>
    </div>
  </div>
</div>

<div class="bg-gray pt-6 pb-7 mt-5">
  <div class="container">
    <h2 class="text-center">Vivre avec la mutation du gène KCNB1</h2>
    <p class="lead">En octobre 2018, 14 patients âgés de 3 à 34 ans ont été diagnostiqués avec la mutation du gène KCNB1 en France. Voici
      leur histoire.</p>
    <div class="row justify-content-center">
      <div class="col-lg-2" style="min-width: 20.25rem; max-width: 20.25rem">
        <div class="card">
          <img src="@asset('images/témoignages/soline-card.jpg')"" alt="Portrait de Soline, 8 ans">
          <div class="card-body">
            <h6 class="card-subtitle"><i class="far fa-comment fa-2x align-middle mr-2"></i> L'histoire de :</h5>
            <h5 class="card-title">Soline, 8 ans</h5>
            <p class="card-text">Je n’avais pas prévu dans mes plans d’avoir une enfant particulière... oh non... le genre de drame qui tombe sans crier garde...</p>
            <a href="test" class="btn btn-blu">Go somewhere</a>
          </div>
        </div>
      </div>
      <div class="col-lg-2 d-none d-sm-block" style="min-width: 20.25rem; max-width: 20.25rem">
        <div class="card">
          <img src="..." class="card-img-top" alt="...">
          <div class="card-body">
            <h6 class="card-subtitle"><i class="far fa-comments"></i> L'histoire de :</h5>
            <h5 class="card-title">Card title</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a href="#" class="btn btn-blu">Go somewhere</a>
          </div>
        </div>
      </div>
      <div class="col-lg-2 d-none d-sm-block" style="min-width: 20.25rem; max-width: 20.25rem">
        <div class="card">
          <img src="..." class="card-img-top" alt="...">
          <div class="card-body">
            <h5 class="card-title">Card title</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a href="#" class="btn btn-blu">Go somewhere</a>
          </div>
        </div>
      </div>
    </div>

    <p class="py-4 text-center mt-3">
      <a href="test">Découvrir tous les témoignages →</a>
    </p>
  </div>
</div>

<div class="container bg-white mt-n7 pt-7">
  <h2 class="text-center">Agir pour la recherche sur le gène KCNB1</h2>
  <p>JEY</p>
  <p>JEY</p>
  <p>JEY</p>
  <p>JEY</p>
  <p>JEY</p>
  <p>JEY</p>
</div>
