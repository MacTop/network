<?php

namespace app\controllers;

use app\models\Booktaking;
use app\models\EditBookForm;
use yii;
use yii\web\Controller;
use app\models\Book;
use app\models\Tag;
use app\models\AddBookForm;

class LibraryController extends Controller
{

    const STATUS_TAKEN = 1;
    const STATUS_UNTAKEN = 2;

    public function actionBooks() {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        if (isset($_POST['id'])) {

            switch($_POST['id']) {
                case 'bytitle':
                    $books = Book::sortByTitle();
                    break;
                case 'byauthor':
                    $books = Book::sortByAuthor();
                    break;
                case 'available':
                    $books = Book::getAvailableBooks();
                    break;
                case 'taken':
                    $books = Book::getTakenBooks();
                    break;
                default:
                    $tags = Tag::findByTitle($_POST['id']);
                    $books = $tags->books;
                    break;
            }

        } else {
            $books = Book::getAvailableBooks();
        }

        $all_tags = Tag::getTags();

        return $this->render('books', array(
            'books' => $books,
            'all_tags' => $all_tags
        ));
    }

    public function actionAddbook() {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $bookForm = new AddBookForm();

        $bookForm->scenario = 'add';

        if ($bookForm->load($_POST) && $bookForm->addBook()) {
            Yii::$app->getResponse()->redirect('@web/library/books');
        } else {
            return $this->render('addbook', array(
                'model' => $bookForm,
            ));
        }
    }

    public function actionEditbook($id = null) {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $book = Book::find($id);

        $bookForm = new AddBookForm;

        $bookForm->scenario = 'edit';

        if ($bookForm->load($_POST) && $bookForm->saveBook($id)) {

            $book = Book::find($id);

            return $this->render('editbook', array(
                'model'   => $bookForm,
                'message' => 'Well done! You successfully edit book.',
                'book' => $book
            ));
        } else {
            return $this->render('editbook', array(
                'model'   => $bookForm,
                'book' => $book
            ));
        }

    }

    public function actionDeletebook() {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $booktakings = Booktaking::findByBookId($_POST['id']);

        foreach ($booktakings as $booktaking) {
            $booktaking->delete();
        }

        $book = Book::find($_POST['id']);

        $tags = $book->tags;

        foreach ($tags as $tag) {
            $book->unlink('tags', $tag);
        }

        $book->delete();

        return Yii::$app->getResponse()->redirect('@web/library/books');
    }

    public function actionTakebook() {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $book = Book::find($_POST['id']);

        $book_take = new Booktaking;
        $book_take->book_id = $_POST['id'];
        $book_take->user_id = Yii::$app->getUser()->getIdentity()->id;
        $book_take->taken = date('Y-m-d');
        $tomorrow  = mktime(0, 0, 0, date("m"), date("d")+1, date("Y"));
        $book_take->returned = date('Y-m-d', $tomorrow);
        $book_take->save();

        $book->status = 'taken';
        $book->save();

        return Yii::$app->getResponse()->redirect('@web/library/books');
    }

    public function actionUntakebook() {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $book = Book::find($_POST['id']);

        $book_take = Booktaking::findByBookIdAndStatus($_POST['id'], self::STATUS_TAKEN);
        $book_take->returned = date('Y-m-d');
        $book_take->status = self::STATUS_UNTAKEN;
        $book_take->save();

        $book->status = 'available';
        $book->save();

        return Yii::$app->getResponse()->redirect('@web/library/books');
    }

}
