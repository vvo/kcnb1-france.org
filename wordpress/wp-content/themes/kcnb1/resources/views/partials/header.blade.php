<div class="socialLinksContainer">
  <svg aria-hidden="true" focusable="false" style="width:0;height:0;position:absolute;">
    <linearGradient id="socialLinksGrad" x1="100%" y1="100%" x2="0%" y2="0%">
      <stop offset="0%" style="stop-color:rgb(241,173,70);stop-opacity:1" />
      <stop offset="100%" style="stop-color:rgb(227,121,73);stop-opacity:1" />
    </linearGradient>
  </svg>
  <div class="container">
    <div class="socialLinks d-flex justify-content-end align-content-around flex-wrap align-between">
      <div class="px-2">Suivez-nous sur</div>
      <a href="https://www.instagram.com/kcnb1.france/" class="block px-2 text-decoration-none"><i
          class="fab fa-instagram"></i> <span class="pl-1">Instagram<span></a>
      <a href="https://www.facebook.com/kcnb1.france/" class="block px-2 text-decoration-none"><i
          class="fab fa-facebook"></i> <span class="pl-1">Facebook</span></a>
    </div>
  </div>
</div>
<header class="mt-3 mb-5" id="mainHeader">
  <nav class="navbar navbar-expand-lg navbar-light container sticky">
    <a class="navbar-brand mr-5" href="{{ home_url('/') }}"><img srcset="@asset('images/logo.png'), @asset('images/logo-2x.png') 2x" src="@asset('images/logo-2x.png')"
        alt="Logo de l'association KCNB1 France" width="153" height="73" /></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      {!! wp_nav_menu($primarymenu) !!}
      <a href="{{ get_permalink(141) }}" class="btn btn-red btn-sm">Faire un don</a>
    </div>
  </nav>
</header>
