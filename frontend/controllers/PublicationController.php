<?php

namespace frontend\controllers;

use common\models\Publication;
use Exception;
use Imagick;
use Imagine\Image\Box;
use Yii;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;

class PublicationController extends Controller
{
    public function actionView($id)
    {
        $publication = Publication::findOne($id);
        if (empty($publication)) {
            throw new HttpException(404);
        }

        return $this->render('view', [
            'publication' => $publication,
            /*'categoryId' => $categoryId,
            'objectPrev' => $objectPrev,
            'objectNext' => $objectNext,*/
        ]);
    }

    public function actionEditDrawings($id)
    {
        if(Yii::$app->user->isGuest)
        {
            return $this->redirect(['site/login']);
        }
        $publication = Publication::findOne($id);
        if (empty($publication)) {
            throw new HttpException(404);
        }

        return $this->render('editDrawings', [
            'publication' => $publication,
        ]);
    }

    public function actionEditTextures($id)
    {
        if(Yii::$app->user->isGuest)
        {
            return $this->redirect(['site/login']);
        }
        $publication = Publication::findOne($id);
        if (empty($publication)) {
            throw new HttpException(404);
        }

        return $this->render('editTextures', [
            'publication' => $publication,
            /*'categoryId' => $categoryId,
            'objectPrev' => $objectPrev,
            'objectNext' => $objectNext,*/
        ]);
    }

    public function actionCreateLayer($id)
    {
        if(Yii::$app->user->isGuest)
        {
            return $this->redirect(['site/login']);
        }

        $publication = Publication::findOne($id);
        /*if (empty($publication)) {
            throw new HttpException(404);
        }*/

        return $this->render('createLayer', [
            'publication' => $publication,
            /*'categoryId' => $categoryId,
            'objectPrev' => $objectPrev,
            'objectNext' => $objectNext,*/
        ]);
    }

    public function actionDelete($id)
    {
        $publication = Publication::findOne($id);
        $publication->deleteFilesFromStorage();
        $publication->delete();
        return $this->goBack();
    }

    public function actionSave($id)
    {
        $data = (!empty($_POST['params'])) ? json_decode($_POST['params'], true) : "empty params";

        //$id = $data["id"];

        $newName = $data["newName"];
        $newDescription = $data["newDescription"];

        $publication = Publication::findOne($id);
        $previousDrawings = $publication->drawings;
        $previousName = $publication->name;
        $previousDescription = $publication->description;

        $publication->name = $newName;
        $publication->description = $newDescription;

        if (strcmp(json_encode($data["newDrawings"]), "") != 2) {
            $newDrawings = json_encode($data["newDrawings"], JSON_UNESCAPED_UNICODE);
            $previousDrawingsJsonArray = json_decode($previousDrawings, true);
            $publication->drawings = $newDrawings;
            if(!is_null($previousDrawingsJsonArray)) {
                for ($i = 0; $i < sizeof($previousDrawingsJsonArray['drawings']); $i++) {
                    $fileName = $previousDrawingsJsonArray['drawings'][$i]['image'];
                    if (strpos($newDrawings, $fileName) == false) {
                        $filePath = Publication::basePath() . '/'
                            . Publication::PREFIX_PATH_DRAWINGS . '/'
                            . $fileName;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            }
        }

        if($publication->update(true, ["name", "description", "drawings"])) {
            Yii::$app->session->setFlash('success', "Успешно сохранено.");
        }
        else if ((strcmp($publication->drawings ,$previousDrawings) == 0)
        && (strcmp($publication->name ,$previousName) == 0)
            && (strcmp($publication->description ,$previousDescription) == 0)){
            Yii::$app->session->setFlash('info', "Изменений нет (изменения уже сохранены).");
        }
        else {
            Yii::$app->session->setFlash('info', "Произошла ошибка про сохранении данных.");
        }
    }

    public function actionSaveLayers($id)
    {
        $data = (!empty($_POST['params'])) ? json_decode($_POST['params'], true) : "empty params";
        //print_r($data);
        $publication = Publication::findOne($id);

        if (strcmp(json_encode($data['newDrawings']), "") != 2) {
            $newDrawings = json_encode($data['newDrawings'], JSON_UNESCAPED_UNICODE);
            $previousDrawings = $publication->drawings;
            $previousDrawingsJsonArray = json_decode($previousDrawings, true);
            $publication->drawings = $newDrawings;

            $layersUrls = $data['layersUrls'];
            $fileNames = $data['layersFilesNames'];

            // Создаем директорию, если не существует
            FileHelper::createDirectory(Publication::basePath() . '/'
                . Publication::PREFIX_PATH_DRAWINGS);

            for($i = 0; $i < sizeof($layersUrls); $i++) {
                $imageBase64 = $layersUrls[$i];
                $img0 = str_replace('data:image/png;base64,', '', $imageBase64);
                $img0 = str_replace(' ', '+', $img0);
                $imageToSave = base64_decode($img0);
                $filePath = Publication::basePath() . '/'
                    . Publication::PREFIX_PATH_DRAWINGS . '/'
                    . $fileNames[$i];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $originalImageSize = $publication->getOriginalImageSize();
                $newImage = new Imagick();
                $newImage->readImageBlob($imageToSave);
                //$newImage->scaleImage($originalImageSize[0], $originalImageSize[1]);
                file_put_contents($filePath, $newImage);
            }
        }

        if(!is_null($previousDrawingsJsonArray)) {
            for($i = 0; $i < sizeof($previousDrawingsJsonArray['drawings']); $i++) {
                $fileName = $previousDrawingsJsonArray['drawings'][$i]['image'];
                if(strpos($newDrawings, $fileName) == false){
                    $filePath = Publication::basePath() . '/'
                        . Publication::PREFIX_PATH_DRAWINGS . '/'
                        . $fileName;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }

        if($publication->update(true, ["drawings"])) {
            Yii::$app->session->setFlash('success', "Успешно сохранено.");
        }
        else if (strcmp($publication->drawings ,$previousDrawings) == 0) {
            Yii::$app->session->setFlash('success', "Успешно сохранено. (Новых слоев нет)");
        }
        else {
            Yii::$app->session->setFlash('error', "Произошла ошибка про сохранении данных. ");
        }
    }

    public function actionSaveTextures($id)
    {
        $data = (!empty($_POST['params'])) ? json_decode($_POST['params'], true) : "empty params";
        //print_r($data);
        $publication = Publication::findOne($id);

        if (strcmp(json_encode($data['newTextures']), "") != 2) {
            $newTextures = json_encode($data['newTextures'], JSON_UNESCAPED_UNICODE);
            $previousTextures = $publication->textures;
            $previousTexturesJsonArray = json_decode($previousTextures, true);
            $publication->textures = $newTextures;

            if (!is_null($previousTexturesJsonArray)) {
                for ($i = 0; $i < sizeof($previousTexturesJsonArray['textures']); $i++) {
                    $fileName = $previousTexturesJsonArray['textures'][$i]['image'];
                    if (strpos($newTextures, $fileName) == false) {
                        $filePath = Publication::basePath() . '/'
                            . Publication::PREFIX_PATH_TEXTURES . '/'
                            . $fileName;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            }

            if ($publication->update(true, ["textures"])) {
                Yii::$app->session->setFlash('success', "Успешно сохранено.");
            } else if (strcmp($publication->textures, $previousTextures) == 0) {
                Yii::$app->session->setFlash('info', "Изменений нет (изменения уже сохранены).");
            } else {
                Yii::$app->session->setFlash('error', "Произошла ошибка про сохранении данных.");
            }
        }
        else {
            Yii::$app->session->setFlash('info', "Произошла ошибка про сохранении данных.");
        }
    }

    public function actionUploadOriginalImage()
    {
        if(Yii::$app->user->isGuest)
        {
            return $this->redirect(['site/login']);
        }
        $model = new Publication();

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->author_id = Yii::$app->user->getId();
                    $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
                    if(!is_null($model->imageFile)) {
                    $newName = md5(uniqid($model->id));
                    $model->image = $newName . '.' . $model->imageFile->extension;
                    if($model->save()) {
                        if ($model->uploadOriginalImage()) {
                            Yii::$app->session->setFlash('success', "Успешно сохранено.");
                            return $this->redirect(['publication/view', 'id' => $model->id]);
                        }
                    }

                    Yii::$app->session->setFlash('error', "При сохранении произошла ошибка.". print_r($model->errors, true));
                }
                else {
                    Yii::$app->session->setFlash('error', "Файл отсутствует.");
                }
            }
        }

        return $this->render('uploadOriginalImage', [
            'model' => $model
        ]);
    }

    public function actionUploadDrawings($id)
    {
        if(Yii::$app->user->isGuest)
        {
            return $this->redirect(['site/login']);
        }
        $model = Publication::findOne($id);
        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->drawingsFiles = UploadedFile::getInstances($model, 'drawingsFiles');
                if(sizeof($model->drawingsFiles) > 0) {
                    if ($model->uploadDrawings()) {
                        $model->drawings = $model->updateDrawings();
                        //var_dump($model->drawings);
                        if($model->update(true, ["drawings"])) {
                            Yii::$app->session->setFlash('success', "Успешно сохранено.");
                            return $this->redirect(['publication/edit', 'id' => $model->id]);
                        }
                        Yii::$app->session->setFlash('error', "При сохранении произошла ошибка.". print_r($model->errors, true));
                    }
                    Yii::$app->session->setFlash('error', "При сохранении произошла ошибка. ". print_r($model->errors, true));
                }
                else {
                    Yii::$app->session->setFlash('error', "Файлы прорисовок отсутствуют.");
                }
            }
        }

        return $this->render('uploadDrawings', [
            'model' => $model
        ]);
    }

    public function actionUploadTextures($id)
    {
        if(Yii::$app->user->isGuest)
        {
            return $this->redirect(['site/login']);
        }
        $model = Publication::findOne($id);

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->texturesFiles = UploadedFile::getInstances($model, 'texturesFiles');
                if(sizeof($model->texturesFiles) > 0) {
                    if ($model->uploadTextures()) {
                        $model->textures = $model->updateTextures();
                       // var_dump($model->textures);
                        if($model->update(true, ["textures"])) {
                            Yii::$app->session->setFlash('success', "Успешно сохранено.");
                            return $this->redirect(['publication/edit-textures', 'id' => $model->id]);
                        }
                        Yii::$app->session->setFlash('error', "При сохранении произошла ошибка.");
                    }
                    Yii::$app->session->setFlash('error', "При сохранении произошла ошибка.");
                }
                else {
                    Yii::$app->session->setFlash('error', "Файлы текстур отсутствуют.");
                }
            }
        }

        return $this->render('uploadTextures', [
            'model' => $model
        ]);
    }

    public function actionDownloadTiff($id)
    {
        //https://www.php.net/manual/en/imagick.writeimages.php
        //1. find model and create empty tiff-result
        $model = Publication::findOne($id);
        $resultTiff = new Imagick();
        $originalImagePath = Publication::basePath() . '/'
            . Publication::PREFIX_PATH_IMAGES . '/'
            . $model->image;
        $originalImage = new Imagick();
        $originalImage->readImage($originalImagePath);
        $resultTiff->addImage($originalImage);

        $originalImageSize = getimagesize($originalImagePath);

        //3. add drawings (if exist) and write attributes about each drawing
        if(sizeof($model->getDrawings()) > 0) {
            foreach ($model->getDrawings() as $drawing) {
                $drawingPath = Publication::basePath(). '/' . Publication::PREFIX_PATH_DRAWINGS . '/' . $drawing['image'];
                $drawingImage = new Imagick();
                $drawingImage->readImage($drawingPath);
                $drawingImage->scaleImage($originalImageSize[0], $originalImageSize[1]);
                $resultTiff->addImage($drawingImage);
            }
        }

        //4. add textures (if exist) and write attributes about each texture
        if(sizeof($model->getTextures()) > 0) {
            foreach ($model->getTextures() as $texture) {
                $texturePath = Publication::basePath(). '/' . Publication::PREFIX_PATH_TEXTURES . '/' . $texture['image'];
                $textureImage = new Imagick();
                $textureImage->readImage($texturePath);
                $textureImage->scaleImage($originalImageSize[0], $originalImageSize[1]);
                $resultTiff->addImage($textureImage);
            }
        }

        try {
            $tiffDir = Publication::basePath(). '/' . "tiff";
            // Создаем директорию, если не существует
            FileHelper::createDirectory($tiffDir);
            $tiffFilename = explode('.', $model->image)[0] . ".tiff";
            if (file_exists($tiffDir . '/' . $tiffFilename)) {
                unlink($tiffDir . '/' . $tiffFilename);
            }

            $resultTiff->writeImages($tiffDir . '/' . $tiffFilename, true);

            $tiffFilepath = $resultTiff->getImageFilename();

            if (file_exists($tiffFilepath)) {
                if (file_exists($tiffFilepath)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="'.basename($tiffFilepath).'"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($tiffFilepath));
                    register_shutdown_function('unlink', $tiffFilepath);
                    ignore_user_abort(true);
                    readfile($tiffFilepath);
                    $resultTiff->clear();
                    $resultTiff->destroy();
                    exit;
                }
            }
        } catch (\ImagickException $e) {
            Yii::$app->session->setFlash('error', "При скачивании файла произошла ошибка: ". $e->getMessage());
        }
    }
}
