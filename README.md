# Passwordless Authentication for CakePHP 3

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install this composer packages is:

```
composer require justinatack/passwordless:dev-master
```

In your config/bootstrap.php file add the following

```
Plugin::load('JustinAtack/Passwordless');
```

In your src/Controller/AppController.php file add the following

```
$this->loadComponent('Auth', [
    'authenticate' => [
        'JustinAtack/Passwordless.Passwordless' => [
            'fields' => [
                'username' => 'email',
                'token' => 'token',
                'token_expiry' => 'token_expiry'
            ],
            'token' => [
                'query' => 'token',
                'length' => 10, // bytes
                'expires' => '+10 mins'
            ]
        ]
    ]
]);
```

## Instructions

Create a Users table with a ```email```, ```token``` and ```token_expiry``` fields. The following columns could be used as a starting point in a Users migration.
```
$table->addColumn('email', 'string', [
    'default' => null,
    'limit' => 255,
    'null' => false,
]);
$table->addColumn('token', 'string', [
    'default' => null,
    'limit' => 255,
    'null' => true,
]);
$table->addColumn('token_expiry', 'datetime', [
    'default' => null,
    'null' => true,
]);
```

In your src/Controller/UsersController.php add the following login method
```
public function login($token = null)
{
    /**
     * Validate token and login user
     *
     */
    if (!empty($this->request->params['pass'][0]) || !empty($this->request->query('token'))) {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Auth->setUser($user);
            return $this->redirect($this->Auth->redirectUrl());
        }
        $this->Flash->error(__('Invalid or expired token, please request new login token'));
        return $this->redirect($this->Auth->redirectUrl());
    }

    /**
     * Validate email and send login token
     *
     */
    if ($this->request->is('post')) {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Flash->success(__('A one-time login URL has been emailed to you'));
            return $this->redirect($this->Auth->redirectUrl());
        }
        $this->Flash->error(__('Email is incorrect'));
        return $this->redirect($this->Auth->redirectUrl());
    }
}
```

Create a login view with a single email form field and submit button. Now add a User and then submit the Login in form with email address. This will generate a token and set the token_expiry. Using this code you can login with the following urls, both will work the same.
```
http://www.example.com/users/login/{token_here}
http://www.example.com/users/login?token={token_here}
```
