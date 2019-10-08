@include('partials.chiffres')

<footer class="bg-grazy pt-5 pb-5">
  <div class="container">
    <div class="row">
      <div class="col">
        <p class="font-weight-bold text-white">Menu</p>
        {!! wp_nav_menu(array(
        'theme_location' => 'primary_navigation',
        'add_li_class' => ''
        )) !!}
      </div>
      <div class="col">
        <p class="font-weight-bold text-white">Suivez-nous</p>
        <p style="line-height: 2rem">
          <a href="https://www.instagram.com/kcnb1.france/" class="block text-decoration-none font-weight-bold"><i
              class="fab fa-instagram fa-2x align-bottom"></i> <span class="pl-1">kcnb1.france<span></a>
          <br /><br /><a href="https://www.facebook.com/kcnb1.france/"
            class="block text-decoration-none font-weight-bold"><i class="fab fa-facebook fa-2x align-bottom"></i> <span
              class="pl-1">kcnb1.france</span></a>
          <br /><br />
          <a href="https://www.linkedin.com/company/association-kcnb1-france/"
            class="block text-decoration-none font-weight-bold"><i class="fab fa-linkedin fa-2x align-bottom"></i> <span
              class="pl-1">association-kcnb1-france</span></a>
      </div>
      <div class="col" style="line-height: 2">
        <p class="font-weight-bold text-white">Contact</p>
        <p><span class="text-white">Association KCNB1 France</span><br />
          <span class="text-gray">6 Rue Des Martins<br />44230 Saint-Sébastien-sur-Loire<br /><a
              href="mailto:contact@kcnb1-france.org">contact@kcnb1-france.org</a></span>
        </p>
      </div>
    </div>
    <hr class="border-gray" />
    <p class="text-gray fs-14">
      <img src="@asset('images/logo-bw.png')" alt="Logo de l'association KCNB1 France, sans les couleurs" width="75"
        height="36" style="margin-right: 1em" />
      ©2018-{{ date("Y") }} <a href="{{ home_url('/') }}"></a> Association KCNB1 France. Tous droits réservés.
    </p>
  </div>
</footer>
