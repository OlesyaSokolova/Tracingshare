function prepareLayersToDraw() {

    var currentSettings = {
        drawings: Array()
    }
    if (typeof settings != "undefined"
        && settings !== ''
        && settings !== "") {

        currentSettings = JSON.parse(JSON.stringify(settings));
    }

//1. Preparations
    //1.1. set original image as background and create thumbnails
    originalImage = new Image();
    originalImage.src = originalImageSrc;
    originalImage.onload = function () {
        var originalImageCtx = drawBackground(originalImage);

        drawOriginalImageLayerThumbnail(originalImage)

        newLayerThumbnail = new Image();
        drawNewLayerThumbnail(originalImage.width, originalImage.height)

        //1.2. create canvas for new layer (to draw)
        var backgroundElement = document.getElementById("background");
        var x = backgroundElement.offsetLeft, y = backgroundElement.offsetTop;
        var canvas = createCanvasToDrawOn(originalImageCtx.canvas.width, originalImageCtx.canvas.height, x, y)

        //1.3. set event listeners
        canvas.onmousedown = startEditing;
        canvas.onmouseup = stopEditing;
        canvas.onmouseout = stopEditing;
        canvas.onmousemove = edit;

        //1.4. init vars and consts
        var context = canvas.getContext("2d");

        const eraserStyle = "rgba(255,255,255,1)";
        const eraserGlobalCompositeOperation = "destination-out";

        const brushGlobalCompositeOperation = context.globalCompositeOperation;
        var brushStyle = "rgba(0,0,0,1)"

        var isDrawing = false;
        var isErasing = false;

        var brushIsClicked = false;
        var eraserIsClicked = false;

        var counter = 0;
        var previousTool;

        //1.5. init buttons

        var clearButton = document.getElementById("clear-layer-button");
        clearButton.addEventListener(
            'click', function (event) {
                context.clearRect(0, 0, canvas.width, canvas.height);
            });

        var brushButton = document.getElementById("brush-btn");
        brushButton.addEventListener(
            'click', function (event) {
                isDrawing = true;
                counter = 1;
                $(this).addClass('active');
                if (previousTool != null && !previousTool.isSameNode(this)) {
                    $(previousTool).removeClass('active');
                }
                previousTool = this;
                brushIsClicked = true;
                eraserIsClicked = false;
            });

        var eraserButton = document.getElementById("eraser-btn");
        eraserButton.addEventListener(
            'click', function (event) {
                counter = 1;
                $(this).addClass('active');
                if (previousTool != null && !previousTool.isSameNode(this)) {
                    $(previousTool).removeClass('active');
                }
                previousTool = this;
                brushIsClicked = false;
                eraserIsClicked = true;
            });

        function startEditing(e) {
            if (counter === 1) {
                isDrawing = false;
                counter = 2;
            }

            if (counter === 2 && brushIsClicked) {
                isDrawing = true;
                isErasing = false;
                context.beginPath();
                context.moveTo(e.pageX - canvas.offsetLeft, e.pageY - canvas.offsetTop);
            }

            if (counter === 2 && eraserIsClicked) {
                isErasing = true;
                isDrawing = false;
                context.beginPath();
                context.moveTo(e.pageX - canvas.offsetLeft, e.pageY - canvas.offsetTop);
            }
        }

        function edit(e) {

            var x, y;
            if (isDrawing === true && counter === 2) {
                context.globalCompositeOperation = brushGlobalCompositeOperation;
                context.strokeStyle = brushStyle;

                x = e.pageX - canvas.offsetLeft;
                y = e.pageY - canvas.offsetTop;

                context.lineTo(x, y);
                context.stroke();
            } else if (isErasing === true && counter === 2) {
                context.globalCompositeOperation = eraserGlobalCompositeOperation;
                context.strokeStyle = eraserStyle;

                x = e.pageX - canvas.offsetLeft;
                y = e.pageY - canvas.offsetTop;

                context.lineTo(x, y);
                context.stroke();
            }
        }

        function stopEditing() {
            isDrawing = false;
            isErasing = false;
        }

        toolbarClassContainer = 'toolbar'

        $('.' + toolbarClassContainer)
            .on('input change', '.color-value', function () {
                $(this).attr('value', $(this).val());
                var newColor = $(this).val();
                var red = parseInt(newColor[1] + newColor[2], 16);
                var green = parseInt(newColor[3] + newColor[4], 16);
                var blue = parseInt(newColor[5] + newColor[6], 16);
                brushStyle = "rgba(" + red + "," + green + "," + blue + ",1)";
                context.strokeStyle = brushStyle;
            })

            .on('input change', '.thickness-value', function () {
                $(this).attr('value', $(this).val());
                var newThickness = $(this).val();
                context.lineWidth = newThickness;
            })

        thumbnailsClassContainer = 'thumbnails-layers'

        $('.' + thumbnailsClassContainer)
            .on('input change', '.orgnl-img-alpha-value', function () {
                $(this).attr('value', $(this).val());
                var newAlpha = parseFloat($(this).val());
                //var drawingImageId = parseInt(($(this).attr('id')).split('_')[1]);
                //drawingsImages[drawingImageId].alpha = newAlpha;
                //updateAllLayers(drawingsImages)
                redrawBackground(newAlpha)
            })

        function redrawBackground(newAlpha) {
            var canvas = document.getElementById('background')
            var ratio = originalImage.width/originalImage.height
            var constWidth = 1000
            var correspondingHeight = constWidth/ratio
            canvas.width = constWidth
            canvas.height = correspondingHeight

            originalImageCtx = canvas.getContext('2d');
            originalImageCtx.globalAlpha = newAlpha;
            //4. fill the context with color of current image
            originalImageCtx.clearRect(0, 0, canvas.width, canvas.height);
            originalImageCtx.globalCompositeOperation = "source-in";
            originalImageCtx.fillRect(0, 0, canvas.width, canvas.height);
            originalImageCtx.globalCompositeOperation = "source-over";
            originalImageCtx.drawImage(originalImage, 0, 0, canvas.width, canvas.height)

            //5. render virtual canvase with image on contextToDrawOn
           // contextToDrawOn.drawImage(canvas, 0, 0, canvas.width, canvas.height);
            originalImageCtx.drawImage(originalImage, 0, 0,canvas.width,  canvas.height);

            //return originalImageCtx
        }

        var saveButton = document.getElementById("save-layer-button");
        saveButton.addEventListener(
            'click', function (event) {
                changeImageColor(context, canvas.width, canvas.height)
                var imageDataUrl = canvas.toDataURL("image/png")
                var imageName = generateRandomImageTitle(prefix, currentSettings.drawings.length+1);
                layerDescription = document.getElementById('layerDesc').value;
                layerTitle = document.getElementById('layerTitle').value;

                var newLayerInfo = {
                    image: imageName,
                    layerParams: {
                        title: layerTitle,
                        alpha: "1",
                        color: "#000000",
                        description: layerDescription
                    }
                }
                currentSettings.drawings.push(newLayerInfo);
                //settingsJSON = JSON.stringify(currentSettings)
                var newData = {
                    newImageName: imageName,
                    newImageUrl: imageDataUrl,
                    newSettings: currentSettings,
                };
                $.ajax({
                    type: "POST",
                    url: "/tracingshare/frontend/web/index.php/publication/save-layer?id=" + publicationId,
                    data: {params: JSON.stringify(newData)},
                    success: function (data) {
                        location.href = "http://localhost/tracingshare/frontend/web/index.php/publication/edit?id=" + publicationId
                    },
                    error: function (xhr, status, error) {
                        alert("Произошла ошибка при сохранении данных:" + xhr);
                    }
                });
            }
        )
    }
}



