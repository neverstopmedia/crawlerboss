<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

	<?php wp_body_open(); ?>

    <?php 
    if( !is_user_logged_in() ){
        ?>
        <div class="text-center container py-40">
            <h1>Login coming</h1>
            <img src="https://media.giphy.com/media/xT9IgG50Fb7Mi0prBC/giphy.gif" alt="">
        </div>
        <?php
        die();
    }
    ?>