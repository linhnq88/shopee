(function (a) {
    if (typeof define === "function" && define.amd) {
        define(["jquery"], a);
    } else {
        a(jQuery);
    }
}(function (a) {
    a.fn.addBack = a.fn.addBack || a.fn.andSelf;
    a.fn.extend({
        actual: function (b, l) {
            if (!this[b]) {
                throw'$.actual => The jQuery method "' + b + '" you called does not exist';
            }
            var f = {absolute: false, clone: false, includeMargin: false, display: "block"};
            var i = a.extend(f, l);
            var e = this.eq(0);
            var h, j;
            if (i.clone === true) {
                h = function () {
                    var m = "position: absolute !important; top: -1000 !important; ";
                    e = e.clone().attr("style", m).appendTo("body");
                };
                j = function () {
                    e.remove();
                };
            } else {
                var g = [];
                var d = "";
                var c;
                h = function () {
                    c = e.parents().addBack().filter(":hidden");
                    d += "visibility: hidden !important; display: " + i.display + " !important; ";
                    if (i.absolute === true) {
                        d += "position: absolute !important; ";
                    }
                    c.each(function () {
                        var m = a(this);
                        var n = m.attr("style");
                        g.push(n);
                        m.attr("style", n ? n + ";" + d : d);
                    });
                };
                j = function () {
                    c.each(function (m) {
                        var o = a(this);
                        var n = g[m];
                        if (n === undefined) {
                            o.removeAttr("style");
                        } else {
                            o.attr("style", n);
                        }
                    });
                };
            }
            h();
            var k = /(outer)/.test(b) ? e[b](i.includeMargin) : e[b]();
            j();
            return k;
        }
    });
}));

jQuery(document).ready(function ($) {
    $(".ot-vm-click #mega-menu-title").click(function () {
        // $("body").toggleClass("active-menu");
        $("#mega_menu").toggleClass("active");

    });
    $("body").click(function (e) {
        var i = $(e.target);
        "mega-menu-title" != i.attr("id") && $("#mega_menu.active").removeClass("active")

    });
    $('.ot-vm-hover').hover(
        function () {
            $('body').addClass("active-menu");
        }, function () {
            $('body').removeClass("active-menu");
        }
    );
    $('#mega_menu').hover(
        function () {
            $('body').addClass("active-menu");
        }, function () {
            $('body').removeClass("active-menu");
        }
    );

    $(".ot-submenu-top #mega_menu > li").each(function (e) {
        let li = $(this);
        let h = li.actual('outerHeight');
        let mt = h * -e + "px";
        let mh = $("#mega_menu").actual('outerHeight');

        li.children(".sub-menu").css({
            "margin-top": mt,
            "min-height": mh,
        });
    });

    $('#mega_menu').superfish({
        speed: 'fast',
        speedOut: 'fast',
        delay: 0,
        // animation:   {opacity:'show',height:'show'},
        onShow: function () {
        },
        onDestroy: function () {
        }
    });

    $(window).bind('scroll', function () {
        if ($('.header-wrapper').hasClass('stuck')) {
            $('#mega_menu').removeClass('active');
        }
    });


});
