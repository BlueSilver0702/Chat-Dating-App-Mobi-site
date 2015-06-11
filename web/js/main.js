if (/iPad/i.test(navigator.userAgent)) {
    jQuery("html").addClass("tablet");
} else if (/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
    jQuery("html").addClass("mobile");
} else {
    jQuery("html").addClass("desktop");
}

/**
 * Simple JavaScript Templating.
 * http://jsperf.com/javascript-templating
 */
var cache = {};

jQuery.tmpl = function (str, data) {
    // figure out if we're getting a template, or if we need to
    // load the template - and be sure to cache the result.
    var fn = !/\W/.test(str) ? cache[str] = cache[str] || this.tmpl(document.getElementById(str).innerHTML) :

    // generate a reusable function that will serve as a template
    // generator (and which will be cached).
    new Function("obj", "var p=[],print=function(){p.push.apply(p,arguments);};" +

    // introduce the data as local variables using with(){}
    "with(obj){p.push('" +

    // convert the template into pure JavaScript
    str.replace(/[\r\t\n]/g, " ").split("<@").join("\t").replace(/((^|@>)[^\t]*)'/g, "$1\r").replace(/\t=(.*?)@>/g, "',$1,'").split("\t").join("');").split("@>").join("p.push('").split("\r").join("\\'") + "');}return p.join('');");

    // provide some basic currying to the user
    return data ? fn(data) : fn;
};

/**
 * Flattens the Array produced by .serializeArray() into an Object
 * with names as keys and values as their values.
 */
jQuery.serializeObject = function(form) {
    var o = {};
    var a = form.serializeArray();
    jQuery.each(a, function() {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || "");
        } else {
            o[this.name] = this.value || "";
        }
    });
    return o;
};

jQuery(document).ready(function ($) {

    /* infield label */
    $(".infieldlabel").inFieldLabels();

    /* fade in content (to hide content flash) */
    $("#wrap").fadeIn(500);

    /* allow user to click through slides */
    $(".desktop .slide, .tablet .slide").each(function () {
        $(this).click(function (e) {
            if (e.target.nodeName == "A") {
                $("html, body").animate({
                    scrollTop: $($(event.target).attr("href")).offset().top
                }, 800);
                e.preventDefault();
            } else {
                if ($(this).attr("id") == "slide3") {
                    $("html, body").animate({
                        scrollTop: $($("#purpose")).offset().top - 140
                    }, 800);
                } else {
                    $("html, body").animate({
                        scrollTop: $($(this).next(".slide")).offset().top
                    }, 800);
                }
            }
        });
    });

    /* navigation dots for slides */
    $(".desktop .slide-nav a").click(function (e) {
        $("html, body").animate({
            scrollTop: $($(this).attr("url")).offset().top
        }, 800);
        e.preventDefault();
    });

    /* make sure all # links animate below toolbar */
    $("a[href^='#'].jump").unbind("click");
    $("a[href^='#'].jump").click(function (e) {
        if ($(this).parents(".slides-nav").attr("class") != "slides-nav") { // if it's not a slide
            $("html, body").animate({
                scrollTop: $($(this).attr("href")).offset().top - 140
            }, 800);
            $(this).addClass("current");
            e.preventDefault();
        }
    });

    /* jump to appropriate place on page from nav-links */
    $("footer .nav-link").click(function () {
        var thisHref = $(this).children("a").attr("href");
        $("html, body").animate({
            scrollTop: $(thisHref).position().top - 146
        }, "slow");
    });

    /* parallax goodness */
    $(".desktop section[data-type='background']").each(function () {
        $window = $(window);
        var $bgobj = $(this);
        $(window).scroll(function () {
            var yPos = -($window.scrollTop() / $bgobj.data("speed"));
            var coords = "50% " + yPos + "px";
            $bgobj.css({
                backgroundPosition: coords
            });
        });
    });

    /* stretch first slide */
    $("#slide1").height($(window).height());
    $(window).resize(function () {
        $("#slide1").height($(window).height());
    });

    /* stretch last slide on mobile */
    $(".mobile #slide1, .tablet #slide1").height($(window).height());

    /* turn on/off current nav links */
    $("#purpose").waypoint(function (direction) {
        if (direction == "down") {
            $(document).scroll(function () {
                var cutoff = $(window).scrollTop();
                var curSec = $.find(".current");
                var curID = $(curSec).attr("id");
                var curNav = $.find("a[name=" + curID + "]");
                $(".section").each(function () {
                    if ($(this).offset().top + $(this).height() > cutoff + 160) {
                        $(".toolbar a").removeClass("current")
                        $(".toolbar a[href='#" + $(this).attr("id") + "']").addClass("current");
                        return false;
                    }
                });
            });
        } else {
            $(document).scroll(function () {
                var cutoff = $(window).scrollTop();
                var curSec = $.find(".current");
                var curID = $(curSec).attr("id");
                var curNav = $.find("a[name=" + curID + "]");
                $(".section").each(function () {
                    if ($(this).offset().top + $(this).height() > cutoff + 160) {
                        $(".toolbar a[href='#" + $(this).attr("id") + "']").removeClass("current");
                        return false;
                    }
                });
            });
        }

    }, {
        offset: 215
    });

    /* highlight current nav item if user clicks */
    $(".toolbar a").click(function () {
        $(this).parent("li").siblings("li").children("a").removeClass("current");
        $(this).parent("li").children("a").addClass("current");
    });

    /* get current geo location */
    $("#info .form-signup button[name=get-location]").on("click", function () {
        navigator.geolocation.getCurrentPosition(function callback(position) {
            $("#info .form-signup input[name=latitude]").val(position.coords.latitude);
            $("#info .form-signup input[name=longitude]").val(position.coords.longitude);
        });
    });

    /* toggle between forms */
    $("#info .js-toggle-login").on("click", function () {
        return $("#info #form-login").show(), $("#info #form-signup").hide(), !1;
    });
    $("#info .js-toggle-signup").on("click", function () {
        return $("#info #form-login").hide(), $("#info #form-signup").show(), !1;
    });

    /* login throw a provider */
    $(".js-provider-link").on("click", function () {
        return window.open($(this).data("provider-url")), !1;
    });

    /* scroll-to-top */
    $(window).scroll(function () {
        if ($(this).scrollTop() > 0) {
            $("a.scroll-to-top").fadeIn(200);
        } else {
            $("a.scroll-to-top").fadeOut(200);
        }
    });
    $("a[href*=#]:not([href=#])").on("click", function () {
        if (location.pathname.replace(/^\//, "") == this.pathname.replace(/^\//, "") && location.hostname == this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $("[name=" + this.hash.slice(1) + "]");
            if (target.length) {
                $("html, body").animate({
                    scrollTop: target.offset().top
                }, 1000);

                return false;
            }
        }
    });
    
    $(".dropdown .dropdown-toggle").click(function(){
        if (!$(this).siblings('.filter').hasClass('menu-open'))
            $(".menu-open").removeClass("menu-open");
        $(this).siblings('.filter').toggleClass("menu-open");
    });
    
    $("#filter_moment .filter .btn").click(function(){
        $(".menu-open").removeClass("menu-open");
        var distance = $('#filter_moment input[name=ra_miles1]:checked').val();
        var gender = 0;
        if ($("#filter_moment .filter #ck_male").is(":checked") && !$("#filter_moment .filter #ck_female").is(":checked")) {
            gender = 1;
        } else if (!$("#filter_moment .filter #ck_male").is(":checked") && $("#filter_moment .filter #ck_female").is(":checked")) {
            gender = 2;
        }
        location.href = filter_moment_path + "?distance=" + distance + "&gender=" + gender;
    });
    
    $("#filter_radar .filter .btn").click(function(){
        $(".menu-open").removeClass("menu-open");
        var distance = $('#filter_radar input[name=ra_miles]:checked').val();
        var gender = 0;
        if ($("#filter_radar .filter #ck_male").is(":checked") && !$("#filter_radar .filter #ck_female").is(":checked")) {
            gender = 1;
        } else if (!$("#filter_radar .filter #ck_male").is(":checked") && $("#filter_radar .filter #ck_female").is(":checked")) {
            gender = 2;
        }
        location.href = filter_radar_path + "?distance=" + distance + "&gender=" + gender;
    });

    if (is_radar == 'radar') {
        if (filter_para2 == 1) {
            $("#filter_radar #ck_female").attr('checked', false);
        } else if (filter_para2 == 2) {
            $("#filter_radar #ck_male").attr('checked', false);
        }
        var $radios = $('input:radio[name=ra_miles]');
        $radios.eq(filter_para1-1).prop('checked', true);
    } else if (is_radar == 'dashboard') {
        if (filter_para2 == 1) {
            $("#filter_moment #ck_female").attr('checked', false);
        } else if (filter_para2 == 2) {
            $("#filter_moment #ck_male").attr('checked', false);
        }
        var $radios = $('input:radio[name=ra_miles1]');
        $radios.eq(filter_para1-1).prop('checked', true);
    }
});
