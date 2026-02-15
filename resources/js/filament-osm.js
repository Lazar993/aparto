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
        apartoOsm.positionDropdown(input, dropdown)

        for (var i = 0; i < results.length; i += 1) {
            (function (result) {
                var item = document.createElement('button')
                item.type = 'button'
                item.textContent = result.display_name
                item.className = 'aparto-osm-item'

                // Item styling
                item.style.padding = '8px 12px'
                item.style.textAlign = 'left'
                item.style.border = 'none'
                item.style.borderRadius = '6px'
                item.style.cursor = 'pointer'
                item.style.fontSize = '14px'
                item.style.color = '#374151'
                item.style.backgroundColor = 'transparent'
                item.style.transition = 'background-color 0.15s'
                item.style.width = '100%'
                item.style.display = 'block'

                item.addEventListener('mouseenter', function () {
                    item.style.backgroundColor = '#f3f4f6'
                })

                item.addEventListener('mouseleave', function () {
                    item.style.backgroundColor = 'transparent'
                })

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

        // Handle IDs with dots (e.g., data.latitude)
        var target = document.querySelector('[id="' + inputId + '"]')
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
        dropdown.style.position = 'fixed'
        dropdown.style.zIndex = '9999'
        dropdown.style.background = '#ffffff'
        dropdown.style.border = '1px solid #d1d5db'
        dropdown.style.borderRadius = '8px'
        dropdown.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)'
        dropdown.style.padding = '4px'
        dropdown.style.display = 'none'
        dropdown.style.gap = '2px'
        dropdown.style.maxHeight = '280px'
        dropdown.style.overflow = 'auto'
        dropdown.style.boxSizing = 'border-box'

        document.body.appendChild(dropdown)
        apartoOsm.positionDropdown(input, dropdown)

        window.addEventListener('resize', function () {
            apartoOsm.positionDropdown(input, dropdown)
        })

        window.addEventListener('scroll', function () {
            if (dropdown.style.display !== 'none') {
                apartoOsm.positionDropdown(input, dropdown)
            }
        }, true)

        return dropdown
    },
    positionDropdown: function (input, dropdown) {
        var rect = input.getBoundingClientRect()
        dropdown.style.width = rect.width + 'px'
        dropdown.style.left = rect.left + 'px'
        dropdown.style.top = (rect.bottom + 4) + 'px'
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

    // Livewire v3 hooks
    if (window.Livewire) {
        Livewire.hook('morph.updated', ({ el, component }) => {
            apartoOsm.init()
        })
    }
}

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    apartoOsmInit()
})

// Also initialize when Alpine is ready (for Filament v3)
document.addEventListener('livewire:navigated', function () {
    apartoOsm.init()
})
