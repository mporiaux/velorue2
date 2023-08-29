/**
 * An object that builds the UI and handles events.
 * Depends on the state object defined in state.js.
 */
var geocoderToolInit = (function() {
  var map;
  var geocoder;
  var boundsRect;
  var revgeoMarker;
  var viewportRect;
  var geocodingResults = [];
  var hashFragment = '';
  var resultTemplate = null;
  var waitInternal = null;
  var state = new GeocoderState();

  /**
   * Wait for a the query-input DOM element to be ready.
   */
  var waitForQueryInput = function() {
    var deferred = $.Deferred();
    waitInternal = setInterval(function() {
      var element = document.getElementById('query-input');
      if (element != null) {
        clearInterval(waitInternal);
        deferred.resolve();
      }
    }, 50);
    return deferred.promise();
  };

  /**
   * Center (and zoom) the map and update the global state.
   * @param {google.maps.LatLng} center A latlng to center the map on.
   * @param {number} zoom The zoom level to set on the map.
   */
  var centerMapOnViewport = function(center, zoom) {
    state.center = center;
    state.zoom = zoom;
    state.centerMap(map);
  };

  /**
   * Center the map on a position provided by W3C Geolocation.
   * @param {Object} position The position provided by W3C Geolocation.
   */
  var centerMapOnGeolocation = function(position) {
    // Only do this only when the viewport is the default one.
    if (state.center == null) {
      centerMapOnViewport(
        new google.maps.LatLng(position.coords.latitude,
                               position.coords.longitude),
        DEFAULT_GEOLOCATION_ZOOM);
    }
  };

  /**
   * Update the URL hash fragment from the global state.
   */
  var updateHashFromState = function() {
    hashFragment = state.buildHash();
    window.location.hash = '#' + escape(hashFragment);
  };

  /**
   * Update the search control from the global state.
   */
  var updateSearchControlFromState = function() {
    document.getElementById('query-input').value = state.query;
  };

  /**
   * Send a request to geocode the global state.
   */
  var geocodeState = function() {
    var request = state.buildGeocodingRequest();

    // Abort if there is not enough input to send a geocoding request.
    if (!request.address && !request.latLng) {
      alert('Please enter a query to geocode, ' +
            'or click on the map to reverse geocode.');
      return;
    }

    // Put a green start marker on the input latlng when reverse geocoding.
    if (request.latLng) {
      revgeoMarker.setIcon(GREEN_X_SYMBOL);
      revgeoMarker.setPosition(request.latLng);
      revgeoMarker.setTitle(request.latLng.toUrlValue(6));
      revgeoMarker.setMap(map);
    } else {
      revgeoMarker.setMap(null);
    }

    console.log('Geocoding: ' + JSON.stringify(request));
    geocoder.geocode(request, handleGeocodingResponse);
  };

  /**
   * Highlight a specific geocoding result.
   * Just make the address components visible in the results control.
   * @param {number} index An index for the global geocodingResults array.
   */
  var highlightResult = function(index) {
    var numResults = geocodingResults.length;
    for (var i = 0; i < numResults; i++) {
      var result = geocodingResults[i];
      var resultDiv = document.getElementById('result-' + i);
      var detailsResultDiv = document.getElementById('details-result-' + i);
      if (i == index) {
        resultDiv.className = 'active-result';
      } else {
        resultDiv.className = 'result';
      }
    }
  };

  /**
   * Fit the visible map area to a LatLngBound.
   * The visible map area is roughly the part that would not be covered by the
   * results control or the search control.
   * @param {google.maps.LatLngBounds} bounds Bounds to fit in the visible area.
   */
  var fitBounds = function(bounds) {
    var mapWidth = document.getElementById('map').offsetWidth;
    var mapHeight = document.getElementById('map').offsetHeight;
    var resultsWidth = PADDING_FOR_MARKERS +
        document.getElementById('results-control-ui').offsetWidth;
    var searchHeight = PADDING_FOR_MARKERS +
        document.getElementById('search-control-ui').offsetHeight;

    var eastboundExpand =
        (bounds.getNorthEast().lng() - bounds.getSouthWest().lng()) *
        resultsWidth / (mapWidth - resultsWidth);
    var newWest = bounds.getSouthWest().lng() - eastboundExpand;
    var newSouthWest = new google.maps.LatLng(
        bounds.getSouthWest().lat(), newWest);
    var newBounds = new google.maps.LatLngBounds(
        newSouthWest, bounds.getNorthEast());
    map.fitBounds(newBounds);
    // Keep track of the resulting viewport in the state.
    state.center = map.getCenter();
    state.zoom = map.getZoom();
  };

  /**
   * Expand a viewport to fit Geocoding inputs: latlng and viewport bias.
   * @param {google.maps.LatLngBounds} viewport The viewport to expand.
   * @return {google.maps.LatLngBounds} The expanded viewport.
   */
  var expandToFitGeocodingInputsInViewport = function(viewport) {
    var expandedViewport = new google.maps.LatLngBounds();
    expandedViewport.union(viewport);

    // Ensure the input latlng marker is visible when reverse geocoding.
    var latlng = parseLatLng(state.query);
    if (latlng) {
      expandedViewport.extend(latlng);
    }

    return expandedViewport;
  };

  /**
   * Center the map on a specific geocoding result.
   * Also make the address components visible in the results control.
   * @param {number} index An index for the global geocodingResults array.
   */
  var selectResult = function(index) {
    updateHashFromState();
    highlightResult(index);
    var result = geocodingResults[index];
    viewportRect.setBounds(result.geometry.viewport);
    viewportRect.setMap(map);
    boundsRect.setBounds(result.geometry.bounds);
    boundsRect.setMap(map);
    fitBounds(expandToFitGeocodingInputsInViewport(result.geometry.viewport));
  };

  /**
   * Adjust the viewport to fit several geocoding results.
   * Exclude countries, and only fit the point location for admin1 areas.
   */

  /**
   * Update the status section of the results control to reflect a geocoding
   * response.
   * @param {google.maps.GeocoderStatus} status Status code from the geocoder.
   */
  var displayStatus = function(status) {
    var statusLine = document.getElementById('status-line');
    statusLineTemplate = ('Status: <span class="{{code}}">' +
                          '<strong>{{code}}</strong> ' +
                          '({{description}}).</span>');
    statusView = {
      code: status,
      description: GEOCODER_STATUS_DESCRIPTION[status]
    };
    statusLine.innerHTML = Mustache.to_html(statusLineTemplate, statusView);
  };

  /**
   * Display all geocoding results in the results control.
   * @param {google.maps.GeocoderResults} results Results from the API geocoder.
   */
  var displayGeocodingResults = function(results) {
    // Remove map overlays from previous geocoding results
    clearMarkers();
    viewportRect.setMap(null);
    boundsRect.setMap(null);
    var resultsDisplay = document.getElementById('results-display-div');
    clearElement(resultsDisplay);
    hideElement(document.getElementById('status-linkbar'));
    if ((results == null) || (results.length < 1)) {
      // Center the map on the input when reverse geocoding finds no results.
      var latlng = parseLatLng(state.query);
      if (latlng) {
        state.center = latlng;
        // Increase zoom if it's too low.
        state.zoom = Math.max(map.getZoom(), 12);
        state.centerMap(map);
      }
      return;
    }

    var resultLength = document.getElementById('status-display-results-length');
    clearElement(resultLength);
    var text = document.createTextNode(results.length);
    resultLength.appendChild(text);
    var jsonLink = document.getElementById('status-display-json-link');
    jsonLink.setAttribute('href', state.buildLinkToWebService('json'));
    var xmlLink = document.getElementById('status-display-xml-link');
    xmlLink.setAttribute('href', state.buildLinkToWebService('xml'));
    displayElement(document.getElementById('status-linkbar'));

    var numResults = results.length;
    for (var i = 0; i < numResults; i++) {
      geocodingResults.push(results[i]);

      var resultDiv = document.createElement('div');
      var view = {
        addressComponents: [],
        i: i,
        icon: letteredMarkerFromIndex(i),
        formattedAddress: results[i].formatted_address,
        locationLatlng: results[i].geometry.location.toUrlValue(6),
        resultTypes: results[i].types.join(', '),
      };

      resultDiv.innerHTML = Mustache.to_html(resultTemplate, view);

      // Append this result to the UI and bind events.
      resultsDisplay.appendChild(resultDiv);
      addMouseEventsToGeocodingResult(i);
      geocodingResults[i].marker = new google.maps.Marker({
        icon: letteredMarkerFromIndex(i),
        map: map,
        position: results[i].geometry.location,
        title: results[i].formatted_address
      });
    }

    selectResult(0);
  };

  /**
   * Add mouse events on a geocoding result.
   * @param {int} i index of the geocoding result to add events to.
   */
  var addMouseEventsToGeocodingResult = function(i) {
    var element = document.getElementById('result-' + i);
    google.maps.event.addDomListener(
        element,
        'click',
        function(e) {
          selectResult(i);
        });
    google.maps.event.addDomListener(
        element,
        'mouseover',
        function(e) {
          highlightResult(i);
        });
  };

  /**
   * Handle a geocoding response.
   * @param {google.maps.GeocoderResults} results Results from the geocoder.
   * @param {google.maps.GeocoderStatus} status Status code from the geocoder.
   */
  var handleGeocodingResponse = function(results, status) {
    displayElement(document.getElementById('results-control-ui'));
    displayStatus(status);
    focusOnQueryInput();
    displayGeocodingResults(results);
  };

  /**
   * Remove all geocoding results' markers from the map.
   */
  var clearMarkers = function() {
    var result;
    while (result = geocodingResults.pop()) {
      result.marker.setMap(null);
    }
  };

  /**
   * Update the global state from the values in the search control.
   */
  var updateStateFromSearchControl = function() {
    state.reset();
    state.query = document.getElementById('query-input').value;

    geocodeState();
    updateHashFromState();
  };

  /**
   * Update the global state from the values in the search control when the
   * users hits "Enter".
   * @param {Object} event The event received.
   */
  var maybeUpdateStateFromSearchControl = function(event) {
    if (!event) var event = window.event;
    if (event.keyCode != 13 /* \n */) return;
    updateStateFromSearchControl();
  };

  /**
   * Request a reverse geocode for a click event.
   * @param {Object} event The event received.
   */
  var reverseGeocode = function(event) {
    // Take the clicked LatLng as query and clear all restrict and bias input.
    state.reset();
    state.query = event.latLng.toUrlValue(6);
    console.log(state);
    updateHashFromState();
    updateSearchControlFromState();
    console.log(state);
    geocodeState();
  };

  /**
   * React to changes in the URL hash fragment.
   */
  var checkHashFragment = function() {
    newHashFragment = unescape(window.location.hash.substring(1));
    if (newHashFragment != hashFragment) {
      hashFragment = newHashFragment;
      state.updateFromHash(hashFragment);
      updateSearchControlFromState();
      geocodeState();
    }
  };

  return function() {
    state.reset();

    // Download and parse the Mustache template for geocoding results.
    resultTemplate = $('#resultsTemplate').text();

    // Create a Google JavaScript Map
    map = new google.maps.Map(document.getElementById('map'), {
        center: (state.center !== null ?
                 state.center : new google.maps.LatLng(0, 0)),
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControlOptions: {
            position: google.maps.ControlPosition.TOP_RIGHT
        },
        panControl: false,
        scaleControl: true,
        zoom: state.zoom,
        zoomControl: true
    });
    geocoder = new google.maps.Geocoder();
    map.addListener('click', reverseGeocode);

    // Fill the UI element and bind add events.
    google.maps.event.addDomListener(
        document.getElementById('geocode-button'),
        'click',
        updateStateFromSearchControl);
    google.maps.event.addDomListener(
        document.getElementById('query-input'),
        'keyup',
        maybeUpdateStateFromSearchControl);

    // Add SearchControl and ResultsControl.
    var searchControlDiv = document.createElement('div');
    var searchControl = new SearchControl(searchControlDiv);
    searchControlDiv.index = 1;
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(searchControlDiv);
    var resultsControlDiv = document.createElement('div');
    var resultsControl = new ResultsControl(resultsControlDiv);
    resultsControlDiv.index = 1;
    map.controls[google.maps.ControlPosition.LEFT_TOP].push(resultsControlDiv);

    // Center the map in the user location, if available.
    if (state.center == null) {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(centerMapOnGeolocation);
      }
    }

    // Create overlays for geocoding result representation..
    boundsRect = new google.maps.Rectangle();
    boundsRect.setOptions({
      clickable: false,
      fillOpacity: 0,
      strokeColor: '#FF0000',
      strokeOpacity: 1,
      strokeWeight: 1
    });
    viewportRect = new google.maps.Rectangle();
    viewportRect.setOptions({
      clickable: false,
      fillOpacity: 0,
      strokeColor: '#0000FF',
      strokeOpacity: 1,
      strokeWeight: 1
    });
    revgeoMarker = new google.maps.Marker();

    // Load initial state from URL hash, then focus on the query input field.
    // Give the browser a little time to have all UI elements ready for this.
    // https://stackoverflow.com/questions/680785/on-window-location-hash-change
    waitForQueryInput().then(function() {
      setInterval(checkHashFragment, 500);
      focusOnQueryInput();
    });
  };
}());

google.maps.event.addDomListener(window, 'load', geocoderToolInit);
