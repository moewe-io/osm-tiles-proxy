window.addEventListener('load', event => {
    if (!window.WPLeafletMapPlugin) {
        console.log("WPLeafletMapPlugin not found, returning!");
        return;
    }

    // iterate any of these: `maps`, `markers`, `markergroups`, `lines`, `circles`, `geojsons`
    let maps = window.WPLeafletMapPlugin.maps;
    maps.forEach(map => {
        map.eachLayer(function (layer) {
            if (layer instanceof L.TileLayer) {
                /** @event tileEvent TileEvent */
                layer.on('tileload', function (tileEvent) {
                    let coords = document.createElement('div');
                    coords.classList.add('leaflet-tile');
                    coords.innerText = `X: ${tileEvent.coords.x} / Y: ${tileEvent.coords.y} / Zoom: ${tileEvent.coords.z}`;
                    coords.style.color = 'red';
                    coords.style['z-index'] = 100;
                    coords.style.background = '#FFFFFF5A';
                    coords.style.width = tileEvent.tile.style.width;
                    coords.style.height = tileEvent.tile.style.height;
                    coords.style.transform = tileEvent.tile.style.transform;
                    coords.style.padding = '5px';
                    coords.style.border = '1px solid red';
                    coords.style.visibility= 'visible';
                    tileEvent.tile.style.border = '1px solid red';
                    tileEvent.tile.parentNode.append(coords);
                });
            }
        });
    });
});