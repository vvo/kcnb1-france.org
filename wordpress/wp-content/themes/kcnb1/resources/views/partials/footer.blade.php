<footer class="bg-grazy pt-5 pb-5">
  <div class="container">
    <div class="row">
      <div class="col">
        <p class="font-weight-bold text-white">Menu</p>
        {!! wp_nav_menu(array(
          'theme_location'    => 'primary_navigation',
          'add_li_class'      => ''
        )) !!}
      </div>
      <div class="col">
        <a href="{{ home_url('/') }}"><img src="@asset('images/logo-bw.png')" alt="Logo de l'association KCNB1 France, sans les couleurs" width="153" height="73" /></a>
      </div>
      <div class="col">
        <p><span class="font-weight-bold text-white">Association KCNB1 France</span><br/>
        <span class="text-gray">6 Rue Des Martins<br/>44230 Saint-Sébastien-sur-Loire<br/><a href="mailto:contact@kcnb1-france.org">contact@kcnb1-france.org</a></span>
        </p>
        <hr class="border-gray" />
        <p><span class="font-weight-bold text-white">Mélissa Cassard (Présidente)</span><br/>
          <span class="text-gray">06 63 60 02 76<br/><a href="mailto:melissa@kcnb1-france.org">melissa@kcnb1-france.org</a></span>
        </p>
        <hr class="border-gray" />
        <div class="d-flex justify-content-between align-content-around flex-wrap align-between" style="line-height: 2rem">
          <a href="https://www.instagram.com/kcnb1.france/" class="block text-decoration-none font-weight-bold"><i class="fab fa-instagram fa-2x align-bottom"></i> <span class="pl-1">kcnb1.france<span></a>
          <a href="https://www.facebook.com/kcnb1.france/" class="block text-decoration-none font-weight-bold"><i class="fab fa-facebook fa-2x align-bottom"></i> <span class="pl-1">kcnb1.france</span></a>
        </div>
      </div>
    </div>
    <hr class="border-gray" />
    <p class="text-gray fs-14">©2018-{{ date("Y") }} Association KCNB1 France. Tous droits réservés.</p>
  </div>
</footer>
