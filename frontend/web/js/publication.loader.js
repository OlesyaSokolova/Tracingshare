function prepareView() {

    //0.save settings for reset
    //defaultSettings = settings;
    if(typeof drawings != "undefined"
        && drawings !== ''
        && drawings !== ""
        && drawings.drawings.length > 0) {
        defaultDrawings = JSON.parse(JSON.stringify(drawings));

        //1. update settings from query (if exist)
        updateSettingsFromQuery(drawings);

        //2. put (updated) settings to url
        updateAllQueryParameters(drawings)

        originalImage = new Image();
        originalImage.src = originalImageSrc;
        originalImage.onload = function () {

            var originalImageCtx = drawOriginalImage(originalImage)
            var drawingsImages = initDrawingsArray(jsonDrawings = drawings)
            addImagesToContext(imagesArray = drawingsImages, contextToDrawOn = originalImageCtx)
            initLayersSettings(jsonDrawings = drawings)

            classNameContainer = 'layers-class'

            $('.' + classNameContainer)
                .on('input change', '.alpha-value', function () {
                    $(this).attr('value', $(this).val());
                    var newAlpha = parseFloat($(this).val());
                    var drawingImageId = parseInt($(this).attr('id'));
                    drawingsImages[drawingImageId].alpha = newAlpha;
                    updateAllLayers(drawingsImages)
                    updateOneQueryParameter(jsonDrawings = drawings, layerId = drawingImageId, key = "alpha", newValue = newAlpha);
                })
                .on('input change', '.color-value', function () {
                    $(this).attr('value', $(this).val());
                    var newColor = $(this).val();
                    var drawingImageId = parseInt($(this).attr('id'));
                    drawingsImages[drawingImageId].color = newColor;
                    updateAllLayers(drawingsImages)
                    updateOneQueryParameter(jsonDrawings = drawings, layerId = drawingImageId, key = "color", newValue = newColor);
                });

            if (drawings.drawings.length !== 0) {
                var descriptionDiv = document.getElementById('description');
                var layerTitle = document.getElementById('layer_title');
                descriptionDiv.innerText = drawings.drawings[0].layerParams.description;
                document.getElementById('layer_' + 0).style.background = "#d6d5d5";
                layerTitle.innerText = drawings.drawings[0].layerParams.title
            }

            var resetButton = document.getElementById("reset-button");
            resetButton.addEventListener('click', function (event) {
                reloadSettings(defaultDrawings, drawingsImages)

                if (drawings.drawings.length !== 0) {
                    var descriptionDiv = document.getElementById('description');
                    var layerTitle = document.getElementById('layer_title');
                    descriptionDiv.innerText = drawings.drawings[0].layerParams.description;
                    document.getElementById('layer_' + 0).style.background = "#d6d5d5";
                    layerTitle.innerText = drawings.drawings[0].layerParams.title
                }
            })
        }
    }
    else {
        originalImage = new Image();
        originalImage.src = originalImageSrc;

        originalImage.onload = function () {
            drawOriginalImage(originalImage)
        }
    }

    if(typeof textures != "undefined"
        && textures !== ''
        && textures !== ""
        && textures.textures.length > 0) {
        addDropdownMenuForTextures(textures);
    }
}

function addDropdownMenuForTextures(jsonTextures) {
    var textures = jsonTextures.textures
    if (Array.isArray(textures)) {
        var texturesSelectElement = document.getElementById("selectTextures");
        var options = '';
        for (let i = 0; i < textures.length; i++) {
            var currentId = "texture_" + i;
            options += '<option id=\'' + currentId + '\'>'
                + textures[i].layerParams.title
                + '</option>'
        }
        texturesSelectElement.insertAdjacentHTML('beforeend', options);
    }
}

function reloadSettings(defaultDrawings, drawingsImages) {
    initLayersSettings(defaultDrawings)
    updateAllLayers(initDrawingsArray(defaultDrawings))
    updateAllQueryParameters(defaultDrawings)
}

function initLayersSettings(jsonDrawings) {
    var drawings = jsonDrawings.drawings
    if (Array.isArray(drawings)) {
        var inputAlpha = '<div id="drawings" style="width: 200px">';
        for (let i = 0; i < drawings.length; i++) {
            if (typeof drawings[i].layerParams.alpha != 'undefined') {
                alphaValue = drawings[i].layerParams.alpha;
                colorValue = drawings[i].layerParams.color;
            } else {
                alphaValue = 1;
            }
            var currentId = "layer_" + i;
            inputAlpha += '<div id=\'' + currentId + '\' class = "bordered_div" style="border:1px solid black; border-radius: 10px; text-align: center; margin-bottom: 10px">';
            inputAlpha += (drawings[i].layerParams.title) + '<br>'
                + '<input type=\'range\' name="alphaChannel" id=\'' + i + '\' class=\'alpha-value\' step=\'0.02\' min=\'0\' max=\'1\' value=\'' + alphaValue + '\' oninput=\"this.nextElementSibling.value = this.value\">'
                + '<br>'
                + '<label for="drawingColor">Цвет: </label>'
                + '<input type="color" id=\'' + i + '\' class =\'color-value\' value=\'' + colorValue + '\' name="drawingColor"></button>' + '<br>';
            inputAlpha += '</div>';
        }
        inputAlpha += '</div>';
        var layersDiv = document.getElementById("layers");
        layersDiv.innerHTML = inputAlpha

        var descriptionDiv = document.getElementById('description');
        var layerTitle = document.getElementById('layer_title');
        for (let i = 0; i < drawings.length; i++) {
            document.getElementById('layer_' + i)
                .addEventListener('click', function (event) {
                    descriptionDiv.innerText = drawings[i].layerParams.description
                    this.style.background = "#d6d5d5";
                    layerTitle.innerText = drawings[i].layerParams.title

                    function clearOtherLayersDivs(i) {
                        for (let j = 0; j < drawings.length; j++) {
                            if(i !== j) {
                                document.getElementById('layer_' + j).style.background = "#ffffff";
                            }
                        }
                    }
                    clearOtherLayersDivs(i)
                });
        }
    }
}

