@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <div class="container bg-white position-relative pb-5 mb-5">
      <h1 class="text-center mb-6">Genèse des recherches<br/> sur le gène KCNB1</h1>
      <div class="row">
        <div class="offset-lg-1 col-lg-3">
          <p>Grâce aux progrès récents dans le domaine de la génétique, de nouveaux gènes impliqués dans des troubles du développement cérébral de l'enfant ont été mis en évidence.</p>
          <p>Par exemple, la régulation du passage du Potassium (K+) est indispensable au bon fonctionnement des neurones. <strong>En 2014, une équipe aux Etats-Unis a ainsi identifié des mutations dans le gène KCNB1 chez des enfants présentant un trouble précoce du développement associé à une épilepsie sévère</strong>. Ce gène code pour une protéine, qui est une composante principale d'un complexe protéique qui forme un canal perméable au Potassium.</p>
          <p>Depuis sa découverte, une trentaine de patients porteurs d'une mutation du gène KCNB1 ont été rapportées dans la littérature médicale. D'autres sont en train d'être reconnus avec les tests génétiques qui deviennent plus accessibles. <strong>Tous les patients rapportés présentent une« encéphalopathie développementale »</strong> c'est-à-dire une anomalie dans le fonctionnement cérébral entraînant un retard global des acquisitions, avec une épilepsie sévère chez la majorité d'entre eux.</p>
        </div>
        <div class="col-lg-3 text-center">
          <img src="@asset('images/programme-de-recherche/cerveau.jpg')" class="img-fluid" alt="Image représentant un cerveau humain" />
        </div>
      </div>
    </div>
    <div class="bg-gray mt-n9 pt-9 pb-6">
      <div class="container">
        <h2 class="text-center mt-5">Le lancement du programme
          <br/>de recherche en France
          <br/>grâce à l'association KCNB1</h2>
        <p class="lead">L'association KCNB1 souhaitait accélérer la connaissance de cette mutation grâce à un programme de recherche dédié.</p>
        <div class="row" style="columns: 2 auto">
          <div class="offset-md-1 col-md-6">
            <p style="columns: 2 auto">Ainsi en novembre 2017, grâce à l'association KCNB1 France et à l'équipe du centre de référence des Epilepsies rares coordonnée par le Pr Nabbout et leurs collaborations internationales, <strong>un programme de recherche clinique et fondamentale a été lancé à l'hôpital Necker et l'institut Imagine</strong> en regroupant 21 patients (18 enfants et 3 jeunes adultes) porteurs d'une mutation dans le gène KCNB1.
            Parmi les 21 patients, 15 ont une nouvelle mutation non rapportée à ce jour dans la littérature.</p>
          </div>
        </div>
        <p class="text-center mt-5">
          <a href="/lassociation" class="btn btn-red btn-lg mt-2">Découvrir l'association</a>
        </p>
      </div>
    </div>
    <div class="container pt-6 pb-6">
      <h2 class="text-center mb-5">L'équipe du programme<br/> de recherche</h1>
      <div class="row">
        <div class="offset-md-1 col-md-2 text-center">
          <img src="@asset('images/programme-de-recherche/rima.jpg')" width="170" height="170" alt="Portrait du professeur Rima Nabbout" />
          <h3 class="mb-4 mt-4 fs-18 text-pink">Le professeur <br/>Rima Nabbout</h3>
          <p class="text-center">Professeur en Neuropédiatrie, docteur en Neurosciences et grande spécialiste de l'épilepsie de l'enfant au sein de l'Hôpital Necker enfants malades à Paris.</p>
        </div>
        <div class="col-md-2 text-center">
          <img src="@asset('images/programme-de-recherche/edor.jpg')" width="170" height="170" alt="Portrait du professeur Rima Nabbout" />
          <h3 class="mb-4 mt-4 fs-18 text-pink">Le professeur <br/>Edor Kabashi</h3>
          <p class="text-center">Chercheur, spécialiste de la génétique de la sclérose latérale amyotrophique (maladie de Charcot).</p>
        </div>
        <div class="col-md-2 text-center">
          <img src="@asset('images/programme-de-recherche/imagine.jpg')" width="170" height="170" alt="Photo d'une employée de l'institut Imagine" />
          <h3 class="mb-4 mt-4 fs-18 text-pink">Une équipe de médecins de l'institut Imagine</h3>
          <p class="text-center">L'Institut Imagine est un lieu unique de recherche et de soins, situé en plein cœur de Paris, sur le campus de l'Hôpital Necker enfants malades.</p>
        </div>
      </div>
    </div>
    <div class="bg-gray pt-6 pb-6">
      <div class="container">
        <h2 class="text-center">Les étapes du programme<br/> de recherche</h2>
        <p class="lead">Recueil des données cliniques et paracliniques pour connaîtreau mieux la maladie :</p>
        <div class="row text-center">
          <div class="offset-md-1 col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">1.</div>
              <p>Dès le 9 décembre 2017, <strong>un recueil exhaustif des données cliniques et paracliniques de ces patients a pu être réalisé</strong> avec l'aide de leurs médecins référents et d'une réunion familles et équipe du centre de référence.</p>
            </div>
          </div>
          <div class="col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">2.</div>
              <p><strong>Les parents ont également été sollicités pour remplir des questionnaires</strong> visant à explorer le versant neuropsychologique de cette pathologie.</p>
            </div>
          </div>
          <div class="col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">3.</div>
              <p>D'autres examens seront proposés afin d'étudier des manifestations extra- neurologiques, comme une possible atteinte cardiaque ou neuro-musculaire périphérique, <strong>ce gène étant exprimé dans ces tissus en dehors du cerveau (EEG et EMG)</strong>.</p>
            </div>
          </div>
        </div>
        <div class="mt-4 row text-center">
          <div class="offset-md-1 col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">4.</div>
              <p>L'analyse des données de cette population va nous permettre d'<strong>améliorer la compréhension de cette maladie</strong>, de ses différentes manifestations et d'établir mieux son histoire naturelle.</p>
            </div>
          </div>
          <div class="col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">5.</div>
              <p><strong>Un registre sera proposé dans le but de l’étendre à l’Europe au sein du réseau EPICARE (Réseau Européen des épilepsies</strong>.</p>
            </div>
          </div>
          <div class="col-md-2 d-flex justify-content-center align-content-end">
            <div class="pb-3 pt-3 pl-4 pr-4 border border-pink flex-fill d-flex">
              <p class="text-pink text-center align-self-center flex-fill">À suivre...</p>
            </div>
          </div>
        </div>
        <p class="lead">Développement d’un modèle animal :</p>
        <div class="row text-center">
          <div class="offset-md-1 col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">1.</div>
              <p>Le programme prévoit également de <strong>développer un modèle animal</strong> afin de mieux comprendre les conséquences délétères de ces mutations génétiques et d’ouvrir la voie au</p>
            </div>
          </div>
          <div class="col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">2.</div>
              <p>Le modèle utilisé initialement sera <strong>le poisson zèbre</strong>, un vertébré excellent pour modéliser les maladies neurodéveloppementales.</p>
            </div>
          </div>
          <div class="col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">3.</div>
              <p><strong>Le modèle KCNB1 sera réalisé en sur-exprimant les formes mutées de ce gène</strong> pour définir le rôle de ces mutations sur le développement du cerveau, du système moteur et celui cardiaque.</p>
            </div>
          </div>
        </div>
        <div class="mt-4 row text-center">
          <div class="offset-md-1 col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">4.</div>
              <p>L’objectif est de recueillir des informations très importantes sur les mécanismes moléculaires et physiologiques que ces mutations entraînent pour <strong>développer dans le futur de nouveaux médicaments spécifiques</strong>.</p>
            </div>
          </div>
          <div class="col-md-2 d-flex">
            <div class="bg-white pb-3 pt-3 pl-4 pr-4">
              <div class="mb-3 fs-30 text-pink font-weight-bold">5.</div>
              <p><strong>Ainsi, à terme, chaque mutation pourrait avoir un traitement thérapeutique personnalisé</strong>.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endwhile
@endsection
