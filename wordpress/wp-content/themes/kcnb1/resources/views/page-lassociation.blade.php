@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <div class="container bg-white position-relative pb-5 mb-5">
      <h1 class="text-center mb-6">L'association<br/> KCNB1 France</h1>
      <p class="lead">Nous nous appelons Candice, Juliette, Laura, Maïa, Sarah, Soline, Arthur, Léonard, Mathéo.L, Vincent et Mathéo LB. Nous avons été diagnostiqués avec une mutation du gène KCNB1. Lorsque nous avons été diagnostiqués, nos familles ont fait des recherches sur internet et ont constaté qu’il y avait très peu d’informations sur cette « maladie ». Chaque famille était seule face à cette mutation de gène avec beaucoup de questions sur la suite. Durant l’été 2017, nos parents ont décidé de se regrouper et de créer l’ « Association KCNB1 France », association loi 1901.</p>
    </div>
    <div class="bg-gray mt-n9 pt-9 pb-6">
      <div class="container">
        <h2 class="text-center mt-5">Objectifs<br/> de l'association</h2>
        <p class="alert alert-pink" style="position: relative; top: 2rem;">L'association a pour buts :</p>
        <div class="row bg-white pt-6 pb-6">
          <div class="offset-md-1 col-md-3">
            <ul>
              <li><strong>De recenser les patients diagnostiqués</strong> avec une
                mutation du gène KCNB1 en France et en Europe,</li>
              <li><strong>D’apporter information, aide et soutien</strong> aux patients et à
                leur famille,</li>
              <li><strong>D’encourager les échanges entre médecins et
                chercheurs</strong> dans le monde entier,</li>
              <li><strong>De favoriser toute recherche clinique ou fondamentale</strong>
                sur la mutation du gène KCNB1,</li>
              <li><strong>D’informer les familles</strong> sur les recherches en cours et
                essais thérapeutiques à venir,</li>
            </ul>
          </div>
          <div class="col-md-3">
            <ul>
              <li><strong>D’organiser des évènements</strong> qui permettent de recueillir
                  des fonds nécessaires pour soutenir et encourager la
                   recherche sur la mutation du gène KCNB1,</li>
              <li><strong>De collecter des fonds</strong> dans le but de soutenir tout projet
                  ou association liés aux malades ainsi qu’à leurs familles,
                   dans un cadre médical, social ou culturel (éducatif),</li>
              <li><strong>De proposer des espaces de rencontre et de travail</strong> pour
                  tous les acteurs engagés dans l’évolution des personnes
                  atteintes de mutation du gène KCNB1.</li>
            </ul>
          </div>
          <div class="offset-md-1 col-md-6">
            <ul>
                <li><strong>De mieux se faire connaître auprès des médecins et
                    thérapeutes</strong> amenés à suivre des patients ayant une mutation du gène KCNB1 en partageant nos expériences
                    et les caractéristiques même de chaque cas recensé,</li>
            </ul>
          </div>
        </div>
        <h2 class="text-center mt-6">Les défis<br/> de l'association</h2>
        <div class="row mt-6">
          <div class="col-md-4 d-flex">
            <div class="bg-white p-5">
              <h3 class="text-center">1/ Recenser les patients</h3>
              <hr class="mt-4 mb-4"/>
              <div class="lg-2columns">
                <p><strong>Début 2017, nous étions 10 familles en contact au niveau mondial grâce au site kcnb1.org.</strong></p>
                <p>Après plusieurs mois de recherches et de démarches auprès d’hôpitaux, de médecins, de forums sur les maladies rares et sur les réseaux sociaux, nous avons recensé 13 patients français âgés de 3 à 30 ans à fin 2017. Nous sommes convaincus qu’il y en a d’autres et nos actions le prouvent.​</p>
                <p>Aujourd’hui, nous sommes environ 100 familles réparties aux 4 coins du monde (France, Etats-Unis, Espagne, Italie, Angleterre, Irlande, Australie, Allemagne, Pologne, Belgique etc…).​ La mutation de ce gène produit des symptômes différents selon les enfants. Elle n’est pas évidente à identifier et beaucoup de médecins n’en ont pas encore entendu parler.​ D’autres enfants souffrants d’épilepsie ont peut-être aussi cette mutation.​</p>
              </div>
            </div>
          </div>

          <div class="col-md-4 d-flex">
            <div class="bg-white p-5">
              <h3 class="text-center">2/ Organiser la 1ère journée
                  de rencontre française
                  Patients - Familles - Médecins</h3>
              <hr class="mt-4 mb-4"/>
              <div class="lg-2columns">
                <p><strong>Le samedi 9 décembre 2017 a eu lieu la première journée de rencontre «KCNB1 FRANCE» à l’hôpital Necker enfants malades à Paris.</strong></p>
                <p>L’organisation de cette journée résulte d’un long travail de recherches et d’échanges entre les familles et les médecins de l’Hôpital Necker.​</p>
                <p>Pour la première fois en France, une équipe de médecins, de chercheurs et de patients se sont réunis pour échanger et discuter du lancement d’un protocole de recherches sur cette mutation extrêmement rare.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-md-4 d-flex">
            <div class="bg-white p-5">
              <h3 class="text-center">3/ Lancement du 1er programme
                  de recherche français
                  sur la mutation du gène KCNB1</h3>
              <hr class="mt-4 mb-4"/>
              <div class="lg-2columns">
                <p><strong>A l’issue de notre journée de rencontre, nous avons eu la confirmation que le premier programme de recherche français sur la mutation du gène KCNB1 allait être lancé par l’Institut IMAGINE et l’hôpital Necker enfants malades à Paris</strong></p>
                <p>Ce fut pour nous blablablab​</p>
              </div>
            </div>
          </div>

          <div class="col-md-4 d-flex">
            <div class="bg-white">
              <div class="bg-white p-5">
                <h3 class="text-center">4/ Un appel aux dons
                    pour soutenir la recherche</h3>
                <hr class="mt-4 mb-4"/>
                <div class="lg-2columns">
                  <p>Au mois de décembre 2017, un premier appel aux dons a été lancé via une cagnotte ouverte en ligne afin de récolter des fonds pour soutenir la recherche sur la mutation du gène KCNB1 et sur les encéphalopathies épileptiques graves.</p>
                  <p><strong>En moins d’un an, l’association KCNB1 France a réussi à récolter plus de 54 000€.</strong>​</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endwhile
@endsection
