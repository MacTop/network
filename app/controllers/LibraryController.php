<?php

namespace app\controllers;

use app\models\Book;
use app\models\BookTaking;
use yii;
use yii\data\Pagination;

class LibraryController extends PjaxController
{
    public function beforeAction($action) {
        // Check user on access
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('/');
            return false;
        }

        return parent::beforeAction($action);
    }

    public function actionAskForBook() {
        $result = array(
            'redirect' => Yii::$app->getUrlManager()->getBaseUrl(),
            'status'   => 'ok'
        );

        if (!isset($_POST['id_ask'])) {
            $result['status'] = 'redirect';
        }

        if ('ok' == $result['status']) {
            $bookTaking = new BookTaking();

            $bookTaking->id_ask = $_POST['id_ask'];

            $result['status'] = $bookTaking->addToAskOrder() ? 'ok' : 'error';
            $result['errors'] = $bookTaking->errors;
        }

        return json_encode($result);
    }

    public function actionBooks($status = 'all', $order = 'author-asc', $page = 1) {
        $user      = Yii::$app->getUser()->getIdentity();
        $bookModel = new Book();
        $where     = array();

        switch($status) {
            case 'available':
                $where['status'] = Book::STATUS_AVAILABLE;
                break;
            case 'taken':
                $where['status'] = Book::STATUS_TAKEN;
                break;
        }

        $bookModel->offset   = $page;
        $bookModel->order_by = str_replace('-', ' ', $order);
        $books_data = $bookModel->getBookList($where, true);
        $books      = array();

        foreach ($books_data['books'] as $key => $book) {
            $books[$key]         = $book->toArray();
            $books[$key]['tags'] = $book->tags;

            switch ($books[$key]['status']) {
                case Book::STATUS_ASK:
                    $books[$key]['status'] = 'ask';
                    $book_taking = BookTaking::findByUserIdAndStatus($user->id, BookTaking::STATUS_ASK);
                    $books[$key]['show_ask'] = !is_object($book_taking);
                    break;
                case Book::STATUS_AVAILABLE:
                    $books[$key]['status']   = 'available';
                    $books[$key]['show_ask'] = true;
                    break;
                case Book::STATUS_TAKEN:
                    $books[$key]['status'] = 'taken';
                    $book_taking = BookTaking::findByUserIdAndStatus($user->id, BookTaking::STATUS_TAKEN);
                    $books[$key]['show_ask'] = is_object($book_taking);
                    break;
            }

            $books[$key]['type'] = $books[$key]['type'] == Book::TYPE_PAPER ? 'Paper' : 'E-book';
        }

        if (isset($books_data['count_total']) && $bookModel->limit < $books_data['count_total']) {
            $pagination = new Pagination(array(
                'pageSize'   => $bookModel->limit,
                'totalCount' => $books_data['count_total']
            ));
        } else {
            $pagination = null;

            if($page != 1) {
                Yii::$app->getResponse()->redirect('/library/books/'.$status.'/'.$order.'/1');
            }

        }

        $param = array(
            'books'      => $books,
            'order'      => $order,
            'page'       => $page,
            'pagination' => $pagination,
            'status'     => $status
        );

        return $this->render('bookList', $param);
    }
}
