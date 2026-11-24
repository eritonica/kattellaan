// Local development helpers. Does nothing unless the site runs on localhost.
(function ($) {
	var host = location.hostname;
	if (host !== "localhost" && host !== "127.0.0.1" && host !== "::1") return;

	// Google's geocoding API needs an API key; use the local stub instead.
	var GEOCODE_URL = "https://maps.googleapis.com/maps/api/geocode/json";
	$.ajaxPrefilter(function (options) {
		if (options.url && options.url.indexOf(GEOCODE_URL) === 0) {
			options.url = "php/geocode-stub.php" + options.url.substring(GEOCODE_URL.length);
			options.crossDomain = false;
		}
	});

	// If the Maps JavaScript API could not load, provide the few pieces used.
	if (typeof window.google === "undefined" || !window.google.maps) {
		window.google = window.google || {};
		window.google.maps = {
			LatLng: function (lat, lng) {
				this.lat = function () { return lat; };
				this.lng = function () { return lng; };
				this.toString = function () { return "(" + lat + ", " + lng + ")"; };
			},
			Map: function () {},
			Marker: function () {},
			MapTypeId: { ROADMAP: "roadmap" }
		};
	}
})(jQuery);
