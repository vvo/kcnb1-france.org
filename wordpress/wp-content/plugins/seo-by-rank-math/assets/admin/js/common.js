!function(e){var t={};function a(n){if(t[n])return t[n].exports;var r=t[n]={i:n,l:!1,exports:{}};return e[n].call(r.exports,r,r.exports,a),r.l=!0,r.exports}a.m=e,a.c=t,a.d=function(e,t,n){a.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},a.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},a.t=function(e,t){if(1&t&&(e=a(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(a.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)a.d(n,r,function(t){return e[t]}.bind(null,r));return n},a.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return a.d(t,"a",t),t},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},a.p="",a(a.s=246)}({246:function(e,t,a){"use strict";var n,r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e};n=jQuery,String.prototype.format||(String.prototype.format=function(){var e=arguments;return this.replace(/{(\d+)}/g,function(t,a){return void 0!==e[a]?e[a]:t})}),String.prototype.trimRight=function(e){return void 0===e&&(e="s"),this.replace(RegExp("["+e+"]+$"),"")},n(function(){window.rankMathAdmin={init:function(){this.misc(),this.tabs(),this.searchConsole(),this.dependencyManager()},misc:function(){void 0!==jQuery.fn.select2&&n("[data-s2]").select2(),n(".cmb-group-text-only,.cmb-group-fix-me").each(function(){var e=n(this),t=e.find(".cmb-repeatable-group"),a=t.find("> .cmb-row:eq(0) > .cmb-th");e.prepend('<div class="cmb-th"><label>'+a.find("h2").text()+"</label></div>"),t.find(".cmb-add-row").append('<span class="cmb2-metabox-description">'+a.find("p").text()+"</span>"),a.parent().remove()}),n(".required, [required]").on("input invalid",function(e){e.preventDefault();var t=n(this);e.target.validity.valid?t.removeClass("invalid animated shake"):t.addClass("invalid animated shake")}),n(".rank-math-collapsible-trigger").on("click",function(e){e.preventDefault();var t=n(this),a=n("#"+t.data("target"));t.toggleClass("open"),a.toggleClass("open")})},searchConsole:function(){var e=this,t=null,a=n("#console_authorization_code"),i=n("#gsc-dp-info"),o=n("#console_profile"),s=a.parent(),c=o.parent(),l=c.find(".button-primary"),d=n("body").hasClass("rank-math-wizard-body--searchconsole")?n("> p:first-of-type",".cmb-form"):n("h1",".rank-math-wrap-settings");a.after(s.find(".button")),o.after(c.find(".button")),o.on("change",function(){null!==o.val()&&0===o.val().indexOf("sc-domain:")?i.removeClass("hidden"):i.addClass("hidden")}).change(),t=l.prev(),a.data("authorized")&&a.hide(),s.on("click",".button-secondary",function(e){e.preventDefault(),window.open(this.href,"","width=800, height=600")}),s.on("click",".button-primary",function(i){i.preventDefault();var o=n(this);o.prop("disabled",!0),a.data("authorized")?e.ajax("search_console_deauthentication").always(function(){o.prop("disabled",!1)}).done(function(){a.val(""),n("#submit-cmb").trigger("click"),"object"===("undefined"==typeof rankMathSetupWizard?"undefined":r(rankMathSetupWizard))&&(a.show(),a.data("authorized",!1),s.find(".button-secondary").show(),o.html("Authorize"),t.prop("disabled",!0),l.prop("disabled",!0))}):(a.addClass("input-loading"),e.ajax("search_console_authentication",{code:a.val()}).always(function(){o.prop("disabled",!1),a.removeClass("input-loading")}).done(function(n){n&&!n.success&&e.addNotice(n.error,"error",d),n&&"fail"===n.status&&e.addNotice(n.body.error_description,"error",d),n&&"success"===n.status&&(a.hide(),a.data("authorized",!0),s.find(".button-secondary").hide(),o.html("De-authorize Account"),l.trigger("click"),t.removeAttr("disabled"))}))}),l.on("click",function(a){a.preventDefault(),l.prop("disabled",!0),t.addClass("input-loading"),e.ajax("search_console_get_profiles").always(function(){l.prop("disabled",!1),n(".console-cache-update-manually").prop("disabled",!1),t.removeClass("input-loading")}).done(function(a){if(a&&!a.success&&e.addNotice(a.error,"error",d),a&&a.success){var r=a.selected||t.val();t.html(""),n.each(a.profiles,function(e,a){t.append('<option value="'+e+'">'+a+"</option>")}),t.val(r||Object.keys(a.profiles)[0]),l.removeClass("hidden")}})})},dependencyManager:function(){var e=this,t=n(".cmb-form, .rank-math-metabox-wrap");n(".cmb-repeat-group-wrap",t).each(function(){var e=n(this),t=e.next(".rank-math-cmb-dependency.hidden");t.length&&e.find("> .cmb-td").append(t)}),n(".rank-math-cmb-dependency",t).each(function(){e.loopDependencies(n(this))}),n("input, select",t).on("change",function(){var t=n(this).attr("name");n('span[data-field="'+t+'"]').each(function(){e.loopDependencies(n(this).closest(".rank-math-cmb-dependency"))})})},checkDependency:function(e,t,a){return"string"==typeof t&&t.includes(",")&&"="===a?t.includes(e):"string"==typeof t&&t.includes(",")&&"!="===a?!t.includes(e):"="===a&&e===t||"=="===a&&e===t||">="===a&&e>=t||"<="===a&&t>=e||">"===a&&e>t||"<"===a&&t>e||"!="===a&&e!==t},loopDependencies:function(e){var t,a=this,r=e.data("relation");e.find("span").each(function(){var e=n(this),i=e.data("value"),o=e.data("comparison"),s=n("[name='"+e.data("field")+"']"),c=s.val();s.is(":radio")&&(c=s.filter(":checked").val()),s.is(":checkbox")&&(c=s.is(":checked"));var l=a.checkDependency(c,i,o);if("or"===r&&l)return t=!0,!1;"and"===r&&(t=void 0===t?l:t&&l)});var i=e.closest(".rank-math-cmb-group");i.length||(i=e.closest(".cmb-row")),t?i.slideDown(300):i.hide()},tabs:function(){var e=n(".rank-math-tabs-navigation");e.length&&e.each(function(){var t=n(this),a=t.closest(".rank-math-tabs"),r=n(">a",t),i=n(">.rank-math-tabs-content>.rank-math-tab",a),o=t.data("active-class")||"active";r.on("click",function(){var e=n(this),t=e.attr("href");return r.removeClass(o),i.hide(),e.addClass(o),n(t).show(),!1});var s=location.hash||localStorage.getItem(a.attr("id"));null===s?r.eq(0).trigger("click"):(s=n('a[href="'+s+'"]',t)).length?s.trigger("click"):r.eq(0).trigger("click"),e.next().css("min-height",t.outerHeight())})},variableInserter:function(e){var t=this,a=n("body"),r=n("input[type=text], textarea",".rank-math-supports-variables");if(e=void 0===e||e,r.length){r.attr("autocomplete","off"),r.wrap('<div class="rank-math-variables-wrap"/>'),n(".rank-math-variables-wrap").append('<a href="#" class="rank-math-variables-button button button-secondary"><span class="dashicons dashicons-arrow-down-alt2"></span></a>'),e&&(n(".rank-math-variables-wrap").after('<div class="rank-math-variables-preview" data-title="Example"/>'),r.on("rank_math_variable_change input",function(e){var a=n(e.currentTarget),r=t.replaceVariables(a.val());r.length>60&&a.attr("name").indexOf("title")>=0&&(r=r.substring(0,60)+"..."),a.parent().next(".rank-math-variables-preview").html(r)}),r.trigger("rank_math_variable_change"));var i=n("<ul/>"),o=n('<div class="rank-math-variables-dropdown"><input type="text" placeholder="Search &hellip;"></div>');n.each(rankMath.variables,function(e){i.append('<li data-var="%'+e+'%"'+(this.example?' data-example="'+this.example+'"':"")+"><strong>"+this.name+"</strong><span>"+this.desc+"</span></li>")}),o.append(i),n(".rank-math-variables-wrap:eq(0)").append(o);var s=n(".rank-math-variables-button, .rank-math-variables-button *, .rank-math-variables-dropdown, .rank-math-variables-dropdown *");n(a).on("click",function(e){n(e.target).is(s)||o.hide()});var c=o.find("input"),l=o.find("li");n(a).on("click",".rank-math-variables-button",function(e){e.preventDefault(),n(this).after(o),l.show(),o.show(),c.val("").focus()}),o.on("click","li",function(e){e.preventDefault();var t=n(this),a=t.closest(".rank-math-variables-wrap").find(">:first-child");a.val(n.trim(a.val())+" "+t.data("var")),a.trigger("rank_math_variable_change").trigger("input"),o.hide()}),o.on("keyup","input",function(e){e.preventDefault();var t=n(this).val().toLowerCase();2>t.length?l.show():l.hide().each(function(){var e=n(this);-1!==e.text().toLowerCase().indexOf(t)&&e.show()})})}},replaceVariables:function(e){return n.each(rankMath.variables,function(t){if(!this.example)return!0;t=t.replace(/\([a-z]+\)/g,"\\(.*?\\)"),e=e.replace(RegExp("%+"+t+"%+","g"),this.example)}),e},ajax:function(e,t,a){return n.ajax({url:rankMath.ajaxurl,type:a||"POST",dataType:"json",data:n.extend(!0,{action:"rank_math_"+e,security:rankMath.security},t)})},addNotice:function(e,t,a,r){r=r||!1;var i=n('<div class="notice notice-'+(t=t||"error")+' is-dismissible"><p>'+e+"</p></div>").hide();a.next(".notice").remove(),a.after(i),i.slideDown(),n(document).trigger("wp-updates-notice-added"),r&&setTimeout(function(){i.fadeOut()},r)}},window.rankMathAdmin.init()})}});