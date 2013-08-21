<?php

namespace app\controllers;

use app\models\Booktaking;
use app\models\EditBookForm;
use yii;
use yii\web\Controller;
use app\models\Book;
use app\models\Tag;
use app\models\AddBookForm;

class LibraryController extends PjaxController
{
    public function actionBooks() {

        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        if (isset($_POST['id_status']) && isset($_POST['id_param']) && $_POST['id_status'] != 'all') {
            $books = Book::getBooksByParams($_POST['id_status'], $_POST['id_param']);
        } else if (isset($_POST['id_status']) && isset($_POST['id_param']) && $_POST['id_status'] == 'all') {
            $books = Book::getAllBooks($_POST['id_param']);
        } else {
            $books = Book::getAllBooks(null);
        }

        if(isset($_POST['sel_tags'])) {
            $selected_tags = $_POST['sel_tags'];
            $books = array();

            foreach($selected_tags as $tag) {
                $tag_curr = Tag::findByTitle($tag);
                $books_by_tag = $tag_curr->books;

                foreach($books as $book_all) {
                    foreach($books_by_tag as $key => $book_by_tag) {
                        if($book_by_tag->title == $book_all->title && $book_by_tag->author == $book_all->author) {
                            unset($books_by_tag[$key]);
                        }
                    }
                }

                $books = array_merge($books, $books_by_tag);
            }
        }

        $all_tags = Tag::getTags();

        if (isset($_POST['partial']) && $_POST['partial'] == 'yes') {
            $this->layout = 'block';
        }

        return $this->render('bookslist', array(
            'books' => $books,
            'all_tags' => $all_tags
        ));
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

        $book_take = Booktaking::findByBookIdAndStatus($_POST['id'], 1);
        $book_take->returned = date('Y-m-d');
        $book_take->status = 2;
        $book_take->save();

        $book->status = Book::STATUS_AVAILABLE;
        $book->save();

        return Yii::$app->getResponse()->redirect('@web/library/books');
    }

}
