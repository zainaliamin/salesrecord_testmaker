<?php

//local
// return [
//   'db' => [
//     // CHANGE dbname, user, pass for your local phpMyAdmin
//     'dsn'  => 'mysql:host=127.0.0.1;dbname=salesrecord_db;charset=utf8mb4',
//     'user' => 'root',
//     'pass' => '',
//   ],
//   // IMPORTANT: path where this sub-app lives
//   'base_url' => '/salesrecord',
  
//   // NEW: where to save receipt images
//   'upload_dir' => __DIR__ . '/../public/proofs',
//   'upload_url' => '/salesrecord/public/proofs',
// ];


//production
return [
  'db' => [
    // CHANGE dbname, user, pass for your local phpMyAdmin
    'dsn'  => 'mysql:host=127.0.0.1;dbname=edums_salesrecord_db;charset=utf8mb4',
    'user' => 'dbuser',
    'pass' => 'password',
  ],
  // IMPORTANT: path where this sub-app lives
  'base_url' => 'https://your-website.com/salesrecord',
  
  // NEW: where to save receipt images
  'upload_dir' => __DIR__ . '/../public/proofs',
  'upload_url' => '/salesrecord/public/proofs',
];

