@extends('layouts.app')

@section('content')
@while(have_posts()) @php the_post() @endphp
<div class="container bg-white position-relative pb-2 mb-5">
  <h1 class="text-center">L'association<br /> KCNB1 France</h1>
  <p class="lead">
    Nous sommes les familles de patients porteurs d'une mutation génétique très rare sur le gène KCNB1. Après des années
    d’errance médicale, lorsque nos enfants sont diagnostiqués, nous
    pensons
    tous qu’ils sont « le
    seul ou la seule patiente française ». <br /><br />Le diagnostic établi, nous faisons tous des recherches sur
    Internet et
    constatons qu’il y a très peu
    d’informations sur cette « maladie ». Les 2 premières familles françaises rentrent en contact en 2016
    via un
    blog
    américain. <br /><br />Nous sommes alors 10
    familles dans le monde, la machine est lancée…
  </p>
</div>
<div class="bg-gray mt-n9 pt-9 pb-6">
  <div class="container">
    <h2 class="text-center mt-2 mb-4">Histoire<br /> de l'association</h2>
    <div class="offset-md-1 col-md-6">
      <p>Au mois de janvier 2017, Mélissa Cassard (présidente de l'association) rencontre le Professeur Rima
        Nabbout lors d’une hospitalisation de sa fille Maïa à l’hôpital Necker-Enfants malades à Paris.</p>

      <p>Le Professeur Nabbout l’encourage à créer une
        association. En quelques mois, grâce aux réseaux sociaux et aux forums de maladies rares, nous rentrons en
        contact avec une,
        puis deux, puis trois… puis 15 familles françaises.</p>

      <p>Au mois d’août 2017, les familles se regroupent et décident de créer l’« Association KCNB1 France ».</p>
    </div>
    <h2 class="text-center mt-5 mb-6">Objectifs<br /> de l'association</h2>
    <div class="row bg-white pt-6 pb-6">
      <div class="offset-md-1 col-md-3">
        <ul>
          <li><strong>Recenser les patients diagnostiqués</strong> avec une
            mutation du gène KCNB1 en France et en Europe,</li>
          <li><strong>Apporter information, aide et soutien</strong> aux patients et à
            leur famille,</li>
          <li><strong>Encourager les échanges entre médecins et
              chercheurs</strong> dans le monde entier,</li>
          <li><strong>Favoriser toute recherche clinique ou fondamentale</strong>
            sur la mutation du gène KCNB1,</li>
          <li><strong>Informer les familles</strong> sur les recherches en cours et
            essais thérapeutiques à venir,</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul>
          <li><strong>Organiser des évènements</strong> qui permettent de recueillir
            des fonds nécessaires pour soutenir et encourager la
            recherche sur la mutation du gène KCNB1,</li>
          <li><strong>Collecter des fonds</strong> dans le but de soutenir tout projet
            ou association liés aux malades ainsi qu’à leurs familles,
            dans un cadre médical, social ou culturel (éducatif),</li>
          <li><strong>Proposer des espaces de rencontre et de travail</strong> pour
            tous les acteurs engagés dans l’évolution des personnes
            atteintes de mutation du gène KCNB1.</li>
        </ul>
      </div>
      <div class="offset-md-1 col-md-6">
        <ul>
          <li><strong>Mieux se faire connaître auprès des médecins et
              thérapeutes</strong> amenés à suivre des patients ayant une mutation du gène KCNB1 en partageant nos
            expériences
            et les caractéristiques même de chaque cas recensé,</li>
        </ul>
      </div>
    </div>
    <h2 class="text-center mt-6">Les défis<br /> de l'association</h2>
    <div class="row mt-6">
      <div class="col-md-4 d-flex">
        <div class="bg-white p-5">
          <h3 class="text-center">1/ Recenser les patients</h3>
          <hr class="mt-4 mb-4" />
          <div class="lg-2columns">
            <p><strong>Début 2017, nous étions 10 familles en contact au niveau mondial grâce au site américain
                <a href="https://www.kcnb1.org">kcnb1.org</a>.</strong>

              <br /><br />Après plusieurs mois de recherches et de démarches auprès d’hôpitaux, de médecins, de forums
              sur les
              maladies rares et sur les réseaux sociaux, nous avons recensé 15 patients
              à fin 2018. <br />Nous sommes convaincus qu’il y en a d’autres.​</p>

            <p>Aujourd’hui, nous sommes environ 100 familles réparties aux 4 coins du monde (France, Etats-Unis,
              Espagne, Italie, Angleterre, Irlande, Australie, Allemagne, Pologne, Belgique etc…).​

              <br /><br />La mutation de
              ce
              gène produit des symptômes différents selon les enfants.
              Elle n’est pas évidente à identifier et
              beaucoup
              de médecins n’en ont pas encore entendu parler.​

              <br /><br />D’autres enfants et adultes souffrant d’épilepsie ont peut-être
              aussi cette mutation.​</p>
          </div>
        </div>
      </div>

      <div class="col-md-4 d-flex">
        <div class="bg-white p-5">
          <h3 class="text-center">2/ Organiser la 1ère journée
            de rencontre française<br />
            "Patients - Familles - Médecins"</h3>
          <hr class="mt-4 mb-4" />
          <div class="lg-2columns">
            <p><strong>Le samedi 9 décembre 2017 a eu lieu la première journée de rencontre «KCNB1 FRANCE» à
                l’hôpital
                Necker-Enfants malades à Paris.</strong></p>
            <p>L’organisation de cette journée résulte d’un long travail de recherches et d’échanges entre les
              familles
              et les médecins de l’Hôpital Necker.​</p>
            <p>Pour la première fois en France, une équipe de médecins, de chercheurs et de patients se sont réunis
              pour
              échanger et discuter du lancement d’un protocole de recherches sur cette mutation extrêmement rare.
            </p>
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
          <hr class="mt-4 mb-4" />
          <div class="lg-2columns">
            <p><strong>A l’issue de cette rencontre, le Professeur Rima Nabbout et le chercheur Edor Kabashi nous
                annoncent</strong>
              qu’un programme de recherche clinique et fondamentale va être lancé par l’Institut Imagine et l’hôpital
              Necker-Enfants malades.</p>

            <p>Pour la première fois, nous avons l’espoir d’un traitement pour nos enfants.</p>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-4 d-flex">
        <div class="bg-white">
          <div class="bg-white p-5">
            <h3 class="text-center">4/ Un appel aux dons
              pour soutenir la recherche</h3>
            <hr class="mt-4 mb-4" />
            <div class="lg-2columns">
              <p>
                <strong>En décembre 2017 un premier appel aux dons est lancé par l’association.</strong>
                Nous parents, frères et sœurs, soutenus par nos familles, sommes déterminés à apporter notre soutien,
                sous toutes ses formes, à l’Institut Imagine et à ses équipes.</p>

              <p>
                Ce programme de recherche, inespéré il y a encore quelques mois, permettra de mieux
                comprendre et peut-être un jour, de traiter la maladie de nos enfants.
                <br />Avec nos enfants, nous avons appris à vivre au jour le jour et à savourer chaque progrès.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endwhile
@endsection
