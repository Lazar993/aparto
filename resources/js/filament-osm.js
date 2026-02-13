var apartoOsm = {
    debounceDelay: 400,
    minChars: 3,
    init: function () {
        var inputs = document.querySelectorAll('input[data-osm-autocomplete="true"]')

        for (var i = 0; i < inputs.length; i += 1) {
            var input = inputs[i]

            apartoOsm.ensureInputId(input)

            if (input.dataset.apartoOsmBound === 'true') {
                continue
            }

            input.dataset.apartoOsmBound = 'true'
            apartoOsm.bindAutocomplete(input)
            // Autocomplete only for admin. Map is shown on frontend.
        }
    },
    ensureInputId: function (input) {
        if (!input.id) {
            input.id = 'aparto-osm-' + Math.random().toString(36).slice(2)
        }
    },
    bindAutocomplete: function (input) {
        var timer = null
        var controller = null
        var dropdown = apartoOsm.createDropdown(input)

        input.addEventListener('input', function () {
            var query = input.value.trim()

            apartoOsm.clearDropdown(dropdown)

            if (query.length < apartoOsm.minChars) {
                return
            }

            if (timer) {
                clearTimeout(timer)
            }

            timer = setTimeout(function () {
                if (controller) {
                    controller.abort()
                }

                controller = new AbortController()

                apartoOsm.fetchResults(query, controller.signal, function (results) {
                    apartoOsm.renderResults(dropdown, input, results)
                })
            }, apartoOsm.debounceDelay)
        })

        document.addEventListener('click', function (event) {
            if (dropdown.contains(event.target) || input.contains(event.target)) {
                return
            }

            apartoOsm.clearDropdown(dropdown)
        })
    },
    fetchResults: function (query, signal, callback) {
        var url = '/osm/search?q=' + encodeURIComponent(query)

        fetch(url, {
            headers: {
                'Accept': 'application/json',
            },
            signal: signal,
        })
            .then(function (response) {
                if (!response.ok) {
                    return []
                }

                return response.json()
            })
            .then(function (data) {
                callback(Array.isArray(data) ? data : [])
            })
            .catch(function () {
                callback([])
            })
    },
    renderResults: function (dropdown, input, results) {
        if (!results.length) {
            return
        }

        dropdown.style.display = 'grid'

        for (var i = 0; i < results.length; i += 1) {
            (function (result) {
                var item = document.createElement('button')
                item.type = 'button'
                item.textContent = result.display_name
                item.className = 'aparto-osm-item'

                item.addEventListener('click', function () {
                    apartoOsm.applyResult(input, result)
                    apartoOsm.clearDropdown(dropdown)
                })

                dropdown.appendChild(item)
            })(results[i])
        }
    },
    applyResult: function (input, result) {
        input.value = result.display_name || input.value
        input.dispatchEvent(new Event('input', { bubbles: true }))

        var city = apartoOsm.findCity(result.address || {})
        apartoOsm.setInputValue(input.dataset.cityInput, city)

        apartoOsm.setInputValue(input.dataset.latInput, result.lat)
        apartoOsm.setInputValue(input.dataset.lngInput, result.lon)
    },
    findCity: function (address) {
        return address.city || address.town || address.village || address.county || address.state || ''
    },
    setInputValue: function (inputId, value) {
        if (!inputId || value === undefined || value === null) {
            return
        }

        var target = document.getElementById(inputId)
        if (!target) {
            return
        }

        target.value = value
        target.dispatchEvent(new Event('input', { bubbles: true }))
    },
    createDropdown: function (input) {
        var existing = document.querySelector('.aparto-osm-dropdown[data-osm-for="' + input.id + '"]')
        if (existing) {
            existing.parentNode.removeChild(existing)
        }

        var dropdown = document.createElement('div')
        dropdown.className = 'aparto-osm-dropdown'
        dropdown.setAttribute('data-osm-for', input.id)
        dropdown.style.position = 'absolute'
        dropdown.style.zIndex = '50'
        dropdown.style.background = '#ffffff'
        dropdown.style.border = '1px solid #e5e7eb'
        dropdown.style.borderRadius = '10px'
        dropdown.style.boxShadow = '0 12px 24px rgba(0, 0, 0, 0.08)'
        dropdown.style.padding = '6px'
        dropdown.style.display = 'none'
        dropdown.style.gap = '4px'
        dropdown.style.maxHeight = '220px'
        dropdown.style.overflow = 'auto'
        dropdown.style.boxSizing = 'border-box'

        document.body.appendChild(dropdown)
        apartoOsm.positionDropdown(input, dropdown)

        window.addEventListener('resize', function () {
            apartoOsm.positionDropdown(input, dropdown)
        })

        return dropdown
    },
    positionDropdown: function (input, dropdown) {
        var rect = input.getBoundingClientRect()
        dropdown.style.width = rect.width + 'px'
        dropdown.style.left = rect.left + window.scrollX + 'px'
        dropdown.style.top = rect.bottom + window.scrollY + 'px'
    },
    clearDropdown: function (dropdown) {
        while (dropdown.firstChild) {
            dropdown.removeChild(dropdown.firstChild)
        }

        dropdown.style.display = 'none'
    },
    ensureMap: function (input) {
        var mapId = input.dataset.mapTarget
        if (!mapId) {
            return
        }

        if (apartoOsm.maps[mapId]) {
            return
        }

        var container = document.getElementById(mapId)
        if (!container) {
            return
        }

        if (container.dataset.apartoMapReady === 'true' || container._leaflet_id) {
            return
        }

        if (!window.L || !window.L.map) {
            return
        }

        var map = L.map(container).setView([44.8176, 20.4633], 12)

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map)

        container.dataset.apartoMapReady = 'true'
        apartoOsm.maps[mapId] = {
            map: map,
            marker: null,
        }
    },
    updateMap: function (input, result) {
        var mapId = input.dataset.mapTarget
        if (!mapId) {
            return
        }

        var mapData = apartoOsm.maps[mapId]
        if (!mapData) {
            return
        }

        var lat = parseFloat(result.lat)
        var lng = parseFloat(result.lon)

        if (isNaN(lat) || isNaN(lng)) {
            return
        }

        if (mapData.marker) {
            mapData.marker.setLatLng([lat, lng])
        } else {
            mapData.marker = L.marker([lat, lng]).addTo(mapData.map)
        }

        mapData.map.setView([lat, lng], 15)
    },
    maps: {},
}

function apartoOsmInit() {
    apartoOsm.init()

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('message.processed', function () {
            apartoOsm.init()
        })
    }
}

document.addEventListener('DOMContentLoaded', function () {
    apartoOsmInit()
})
