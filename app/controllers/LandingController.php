	<?php
	defined('BASEPATH') OR exit('No direct script access allowed');
	class LandingController extends PulseController
	{
	    public function __construct()
	    {
	        parent::__construct();
	        if ($this->session->get('user_id')) {
	            $this->function->redirectTo('dashboard');
	        }
	    }
	    public function index()
	    {
	        $data = [
	            'title' => 'Bienvenido a Pulse App',
	            'csrf_token' => $this->csrf->getTokenField()
	        ];
	        $this->layout('main', 'landing/index', $data);
	    }
	}