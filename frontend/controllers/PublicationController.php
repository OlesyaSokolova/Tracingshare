<?php

namespace frontend\controllers;

use common\models\Publication;
use Yii;
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

    public function actionEdit($id)
    {
        $publication = Publication::findOne($id);
        if (empty($publication)) {
            throw new HttpException(404);
        }

        return $this->render('edit', [
            'publication' => $publication,
            /*'categoryId' => $categoryId,
            'objectPrev' => $objectPrev,
            'objectNext' => $objectNext,*/
        ]);
    }

    public function actionCreateLayer($id)
    {
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
        $publication->name = $newName;
        $publication->description = $newDescription;

        if (strcmp(json_encode($data["newDrawings"]), "") != 2) {
            $newDrawings = json_encode($data["newDrawings"], JSON_UNESCAPED_UNICODE);

            $previousDrawingsJsonArray = json_decode($publication->drawings, true);
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
        else {
            Yii::$app->session->setFlash('info', "Изменений нет.");
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
                file_put_contents($filePath, $imageToSave);
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
            Yii::$app->session->setFlash('info', "Произошла ошибка про сохранении данных. ". print_r($publication->errors, true));
        }
    }

    public function actionUploadOriginalImage()
    {
        $model = new Publication();

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->author_id = Yii::$app->user->getId();
                    $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
                    if(!is_null($model->imageFile)) {
                    $model->image = $model->imageFile->baseName . '.' . $model->imageFile->extension;
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
        $model = Publication::findOne($id);

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->drawingsFiles = UploadedFile::getInstances($model, 'drawingsFiles');
                if(is_null($model->drawingsFiles)) {
                    if ($model->uploadDrawings()) {
                        $model->drawings = $model->updateDrawings();
                        var_dump($model->drawings);
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
}
