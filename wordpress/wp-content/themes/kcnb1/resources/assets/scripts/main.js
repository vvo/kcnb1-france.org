// import external dependencies
import 'jquery';
import { library, dom } from '@fortawesome/fontawesome-svg-core';
import { faFacebook, faInstagram } from '@fortawesome/free-brands-svg-icons';
import { faComment, faCalendarAlt, faStickyNote } from '@fortawesome/free-regular-svg-icons';
import { faStream, faMapMarkerAlt } from '@fortawesome/free-solid-svg-icons';
import './autoload/**/*'

import Router from './util/Router';
import common from './routes/common';
import home from './routes/home';
import aboutUs from './routes/about';

library.add(faFacebook, faInstagram, faComment, faStream, faCalendarAlt, faStickyNote, faMapMarkerAlt);
dom.watch();

/** Populate Router instance with DOM routes */
const routes = new Router({
  // All pages
  common,
  // Home page
  home,
  // About Us page, note the change from about-us to aboutUs.
  aboutUs,
});

// Load Events
jQuery(document).ready(() => routes.loadEvents());
