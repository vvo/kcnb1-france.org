@extends('layouts.app')

@section('content')
@while(have_posts()) @php the_post() @endphp
<div class="container bg-white position-relative">
  <h1 class="text-center">La mutation <br />du gène KCNB1</h1>
  <p class="lead">Fin 2014, des mutations dans le gène KCNB1 ont <a
      href="https://www.ncbi.nlm.nih.gov/pubmed/25164438">été découvertes aux États-Unis</a> chez des personnes
    atteintes d'encéphalopathies épileptiques. Depuis, de nombreuses recherches ont étés menées afin de confirmer la
    relation entre la mutation du gène KCNB1 et les symptômes des personnes atteintes mais aussi d'identifier et de
    recenser plus précisément les cas à travers le monde.</p>
</div>
<div class="bg-gray mt-n9 pt-9 pb-6">
  <div class="container">
    <h2 class="text-center mt-5 mb-6">L'ADN, <br />nos chromosomes et gènes</h2>
    <div class="row">
      <div class="offset-md-1 col-md-3">
        <p>Notre corps est constitué de milliards de cellules. À l'intérieur de ces cellules se trouvent nos
          chromosomes. Ces structures contiennent notre ADN (notre code génétique) qui indiquent au corps comment se
          développer et fonctionner. Les chromosomes contiennent eux des informations génétiques qui se regroupent en
          <strong>gènes</strong>. Chaque gène a un rôle spécifique dans le corps lorsqu'il fonctionne correctement.</p>
        <p>Des mutations génétiques peuvent survenir sur cette séquence d'ADN qui code pour partie la protéine KCNB1. Il
          est possible de vérifier cet encodage de façon précise. Lorsqu'une anomalie survient et qu'il provoque un
          dysfonctionnement génique alors on parle d'une <strong>mutation</strong>.</p>
      </div>
      <div class="col-md-3">
        <img src="@asset('images/le-gene-KCNB1/sequence.png')" class="img-fluid"
          alt="Image représentant une séquence ADN" />
      </div>
    </div>
  </div>
</div>
<div class="container pt-6 pb-6">
  <h2 class="text-center mb-5">Le gène KCNB1 <br />et ses fonctions</h1>
    <div class="row">
      <div class="offset-md-1 col-md-3 text-center">
        <img src="@asset('images/le-gene-KCNB1/potassium.png')" width="300" class="img-fluid"
          alt="Image représentant une séquence ADN" />
      </div>
      <div class="col-md-3">
        <br /><br />
        <p>Une des fonctions du gène KCNB1 est d'aider à la <strong>formation de pores (ou tunnels) de passage du
            potassium pour les cellules</strong>. Ces tunnels régulent ensuite l'afflux de potassium vers l'intérieur et
          aussi l'extérieur de la cellule. La bonne <strong>régulation du potassium est essentielle</strong> et aide au
          contrôle de plusieurs fonctions dans les cellules dans tout le corps humain.</p>
      </div>
    </div>
</div>
<div class="bg-gray pt-6 pb-6">
  <div class="container">
    <h2 class="text-center">La mutation, <br />du gène KCNB1</h2>
    <p class="lead">Les mutations du gène KCNB1 peuvent êtres héritées d'un parent porteur (sain) ou survenir "de novo"
      en raison d'une erreur dans la réplication cellulaire.</p>
    <div class="row">
      <div class="offset-md-1 col-md-3">
        <p>Lorsqu'une personne est atteinte d'une mutation du gène KCNB1, alors <strong>le contrôle du flux du potassium
            dans ses cellules se trouve perturbé</strong>. La compréhension exacte des fonctions du gène KCNB1 est
          récente. Néanmoins les personnes touchées souffrent fréquemment d'<strong>encéphalopathies épileptiques
            ainsi que de retards de développement</strong>.</p>
      </div>
      <div class="col-md-3">
        <p>Ces crises sont souvent présentes dès l'enfance et peuvent êtres variées dans leur déroulement. <strong>Il
            est bien souvent difficile de contrôler ces crises avec des médicaments</strong>, on parle de crises
          pharmaco-résistantes.</p>
      </div>
    </div>
    <div class="bg-white">
      <p class="lead">Aujourd'hui plus de 20 mutations dans le gène KCNB1 ont été détectées.
        Le type de mutation et sa localisation sur le gène ne prédit pas la sévérité
        de la maladie.</p>
    </div>

    <h2 class="text-center mt-6">Principaux <br />symptômes</h2>
    <p class="lead">Chaque personne peut présenter un ou plusieurs de ces symptômes et nous les listons pour vous aider
      et guider des parents qui se poseraient la question d'un diagnostic avec le corps médical. <strong>Ils ne
        constituent en aucun cas un avis médical
        de notre part.</strong></p>
    <div class="row bg-white p-4">
      <div class="offset-md-1 col-md-2">
        <ul>
          <li>Encéphalopathie</li>
          <li>Épilepsies pharmaco-résistantes</li>
          <li>Myoclonies</li>
          <li>Crises tonico cloniques</li>
          <li>Spasmes</li>
          <li>Absence crise partielle</li>
          <li>Hypotonie</li>
        </ul>
      </div>
      <div class="col-md-2">
        <ul>
          <li>Déficience intellectuelle sévère</li>
          <li>Troubles autistiques</li>
          <li>Polyhandicap</li>
          <li>Chutes</li>
          <li>Colères</li>
          <li>Retards de développement</li>
          <li>Ataxie</li>
        </ul>
      </div>
      <div class="col-md-2">
        <ul>
          <li>Hyperactivité</li>
          <li>Troubles sensoriels</li>
          <li>Troubles alimentaires</li>
          <li>Troubles du sommeil</li>
          <li>Instabilité émotionnelle</li>
          <li>Anxiété</li>
          <li>Hypertonie</li>
        </ul>
      </div>
    </div>
    <div class="row mt-5">
      <div class="offset-md-1 col-md-3">
        <p>Au total <strong>toute évolution atypique inquiétante</strong> du développement sensori moteur quelque soit
          l'âge est à même de faire <strong>envisager une maladie neurologique sous jacente comme la
            mutation KCNB1</strong>.</p>
      </div>
      <div class="col-md-3">
        <p>Cette mutation génétique nouvellement identifiée appartient au <strong>syndrome microdélétionnels</strong> et
          doit être recherchée dans le cadre des recherches étiologiques menées par un médecin neuro pédiatre qui est
          amené à rencontrer l'enfant.</p>
      </div>
    </div>
  </div>
</div>
<div class="container pt-6 pb-6">
  <h2 class="text-center mb-5">Bibliographie</h1>
    <ul class="offset-md-1">
      <li><a
          href="https://epilepsygenetics.net/2017/09/18/kcnb1-encephalopathy-widening-the-phenotypic-spectrum/">http://epilepsygenetics.net/2017/09/18/kcnb1-encephalopathy-widening-the-phenotypic-spectrum/</a>
      </li>
      <li><a
          href="https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4192091/">https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4192091/</a>
      </li>
      <li><a
          href="https://onlinelibrary.wiley.com/doi/epdf/10.1111/epi.13250">https://onlinelibrary.wiley.com/doi/epdf/10.1111/epi.13250</a>
      </li>
      <li><a
          href="https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4192091/">https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4192091/</a>
      </li>
      <li><a href="https://www.ncbi.nlm.nih.gov/pubmed/28806457">https://www.ncbi.nlm.nih.gov/pubmed/28806457</a></li>
      <li><a
          href="https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5733249/">https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5733249/</a>
      </li>
      <li><a
          href="https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5733250/">https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5733250/</a>
      </li>
    </ul>
</div>
@endwhile
@endsection
