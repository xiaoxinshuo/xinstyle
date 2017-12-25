!function(t) {
    t.fly = function(e, i) {
        var n = {
            version: "1.0.0",
            autoPlay: !0,
            vertex_Rtop: 20,
            speed: 1.2,
            start: {},
            end: {},
            onEnd: t.noop
        },
        o = this,
        h = t(e);
        o.init = function(t) {
            this.setOptions(t),
            !!this.settings.autoPlay && this.play()
        },
        o.setOptions = function(e) {
            this.settings = t.extend(!0, {},
            n, e);
            var i = this.settings,
            o = i.start,
            a = i.end;
            h.css({
                marginTop: "0px",
                marginLeft: "0px",
                position: "fixed"
            }).appendTo("body"),
            null != a.width && null != a.height && t.extend(!0, o, {
                width: h.width(),
                height: h.height()
            });
            var s = Math.min(o.top, a.top) - Math.abs(o.left - a.left) / 13;
            s < i.vertex_Rtop && (s = Math.min(i.vertex_Rtop, Math.min(o.top, a.top)));
            var p = Math.sqrt(Math.pow(o.top - a.top, 2) + Math.pow(o.left - a.left, 2)),
            r = Math.ceil(Math.min(Math.max(Math.log(p) / .05 - 75, 30), 100) / i.speed),
            l = o.top == s ? 0 : -Math.sqrt((a.top - s) / (o.top - s)),
            f = (l * o.left - a.left) / (l - 1),
            d = a.left == f ? 0 : (a.top - s) / Math.pow(a.left - f, 2);
            t.extend(!0, i, {
                count: -1,
                steps: r,
                vertex_left: f,
                vertex_top: s,
                curvature: d
            })
        },
        o.play = function() {
            this.move()
        },
        o.move = function() {
            var e = this.settings,
            i = e.start,
            n = e.count,
            o = e.steps,
            a = e.end,
            s = i.left + (a.left - i.left) * n / o,
            p = 0 == e.curvature ? i.top + (a.top - i.top) * n / o: e.curvature * Math.pow(s - e.vertex_left, 2) + e.vertex_top;
            if (null != a.width && null != a.height) {
                var r = o / 2,
                l = a.width - (a.width - i.width) * Math.cos(r > n ? 0 : (n - r) / (o - r) * Math.PI / 2),
                f = a.height - (a.height - i.height) * Math.cos(r > n ? 0 : (n - r) / (o - r) * Math.PI / 2);
                h.css({
                    width: l + "px",
                    height: f + "px",
                    "font-size": Math.min(l, f) + "px"
                })
            }
            h.css({
                left: s + "px",
                top: p + "px"
            }),
            e.count++;
            var d = window.requestAnimationFrame(t.proxy(this.move, this));
            n == o && (window.cancelAnimationFrame(d), e.onEnd.apply(this))
        },
        o.destroy = function() {
            h.remove()
        },
        o.init(i)
    },
    t.fn.fly = function(e) {
        return this.each(function() {
            void 0 == t(this).data("fly") && t(this).data("fly", new t.fly(this, e))
        })
    }
} (jQuery);