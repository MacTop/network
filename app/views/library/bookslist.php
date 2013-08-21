<?php

use yii\helpers\Html;
use app\models\Book;
use app\models\Booktaking;
use app\models\User;
?>

<div class="col-lg-offset-1">
    <h1> Library </h1>
    <br/>
    <ul class="nav nav-pills">

        <li class="active"><?php echo Html::a('Show all books', null, array(
                'id' => 'all',
                'onclick' => 'return sortBooks(this)',
                'class' => 'cursorOnNoLink'
            )); ?>
        </li>

        <li><?php echo Html::a('Show available books', null, array(
                'id' => 'available',
                'onclick' => 'return sortBooks(this)',
                'class' => 'cursorOnNoLink'
            )); ?>
        </li>

        <li><?php echo Html::a('Show taken books', null, array(
                'id' => 'taken',
                'onclick' => 'return sortBooks(this)',
                'class' => 'cursorOnNoLink'
            )); ?>
        </li>

    </ul>
    <br/>
    <ul class="nav nav-pills">

        <li><?php echo Html::a('Sort books by title', null, array(
                'id' => 'title',
                'onclick' => 'return sortBooks(this)',
                'class' => 'cursorOnNoLink'
            )); ?>
        </li>

        <li><?php echo Html::a('Sort books by author', null, array(
                'id' => 'author',
                'onclick' => 'return sortBooks(this)',
                'class' => 'cursorOnNoLink'
            )); ?>
        </li>
    </ul>

    <br/>

    <h4 class="tag">
        <p>
            <?php foreach ($all_tags as $tag) {
                echo Html::a($tag->title, null, array(
                        'id' => $tag->title,
                        'onclick' => 'return showByTags(this)',
                        'class' => 'label label-info',
                    )).' ';
            } ?>
        </p>
    </h4>

    <div class="bookslist">
        <?php foreach ($books as $book):?>
            <ul class="nav nav-list">
                <hr>
                <li>
                    <p><?php echo $book->author; ?></p>
                    <p class='lead'>
                        <?php echo $book->title; ?>
                        <?php if ($book->status == 'available'): ?>
                        <span class='label label-success'><?php echo $book->status; ?></span></p>
                    <?php else: ?>
                        <span class='label label-danger'><?php echo $book->status; ?></span></p>
                        <small> Taken by
                            <?php $booktake = Booktaking::findByBookIdAndStatus($book->id, 1);
                            if ($booktake) {
                                echo User::getUserNameById($booktake->user_id).' '.$booktake->taken.
                                    '. Will be returned '.$booktake->returned.'.';
                            }?>
                        </small>
                        <br/><br/>
                    <?php endif; ?>

                    <blockquote>
                        <p><?php echo $book->description; ?></p>
                    </blockquote>

                    <?php if ($book->type == 2): ?>
                        <span class='label label-success'>
                        <?php echo Html::a('Download Ebook', $book->link, array('target' => '_blank')); ?>
                    </span>
                    <?php endif;?>
                </li>
            </ul>

            <h4 class="tag">
                <?php
                    $book_current = Book::find($book->id);
                    $tags_by_book = $book_current->tags;

                    foreach ($tags_by_book as $tag) {
                        echo Html::a($tag->title, null, array(
                                'id' => $tag->title,
                                'onclick' => 'return showByTags(this)',
                                'class' => 'label label-info'
                            )).' ';
                    } ?>
            </h4>
            <br/>
            <?php if($book->status == 'available'): ?>
                <ul class="nav nav-pills">
                    <li><?php echo Html::a('Take book', null, array(
                            'id' => $book->id,
                            'class' => 'cursorOnNoLink',
                            'onclick' => 'return takeBook(this)',
                        )); ?></li>
                </ul>
            <?php else: $book_take = Booktaking::findByBookIdAndStatus($book->id, 1);?>
                <?php if ($book_take && Yii::$app->getUser()->getId() == $book_take->user_id): ?>
                    <ul class="nav nav-pills">
                        <li>
                            <?php echo Html::a('Untake book', null, array(
                                'id' => $book->id,
                                'class' => 'cursorOnNoLink',
                                'onclick' => 'return untakeBook(this)'
                            )); ?>
                        </li>
                    </ul>
                <?php endif;?>
            <?php endif;?>
        <?php endforeach;?>
    </div>
</div>