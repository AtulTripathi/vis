<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Welcome extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Customer_model', 'customerM');
		$this->load->model('admin/Lead_model', 'leadM');
		$this->load->model('admin/WebUser_model', 'w_user');
		$this->load->model('admin/Hr_model', 'hrM');
		$this->load->model('User_model', 'user');
		$this->form_validation->set_error_delimiters('<div class="alert alert-danger ">', '</div>');
		$this->load->helper('string');
		$this->load->helper('cookie');
		$this->load->library('email');

		ini_set('max_execution_time', 0);
		ini_set("memory_limit", "1024M");

		// 			For trendy server
		//$this->email->initialize(array(
		//    'mailtype'      => 'html'
		//'smtp_user' => 'visindiark',
		//'smtp_pass' => 'rkassociates_2019',
		//));
		//          For Live Server ==========
		$this->email->initialize(array(
			'protocol'  => 'smtp',
			'smtp_host' => 'smtp.sendgrid.net',
			'smtp_user' => 'visindiark',
			'smtp_pass' => 'rkassociates_2019',
			'mailtype' => 'html',
			'wordwrap' => TRUE,
			'smtp_port' => 587,
			'crlf'      => "\r\n",
			'newline'   => "\r\n"
		));
	}

	public function is_logged_in()
	{
		$admin_data = $this->session->userdata('adminData');
		if (!empty($admin_data)) {
			redirect(site_url('admin/dashboard'));
		}
	}

	function get_pwd_state_data()
	{
		$this->load->model('admin/ManageKnowledgeRepository_model', 'home');
		return $this->home->get_where('tbl_cpwd_states', 'status', 1);
	}

	function get_cpwd_category()
	{
		$distrct = $this->db->get_where('tbl_cpwd_district', array('state_id' => $this->input->post('id')))->result_array();
		echo json_encode($distrct);
	}

	function get_cpwd_category_by_name()
	{
		$state_name = trim($this->input->post('id'));
		$distrct = $this->db->get_where('tbl_cpwd_district', array('state_name' => $this->input->post('state_name')))->result_array();
		echo json_encode($distrct);
	}


	function get_cpwd_category_pdf()
	{
		$pdf = $this->db->get_where('tbl_cpwd_data', array('district_id' => $this->input->post('id')))->result_array();
		echo json_encode($pdf);
	}

	// Check login status if not login redirect it to login page
	public function is_not_logged_in()
	{
		$admin_data = $this->session->userdata('adminData');

		if (empty($admin_data)) {
			redirect(site_url('admin/index'));
		}
	}

	public function index()
	{
		$this->is_logged_in();
		$data['allowBALogin'] 		=	0;
		$data['branch_id'] 			= '';
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$this->load->view('admin/login_page', $data);
	}

	public function login_with_otp()
	{
		$this->is_logged_in();
		$data['allowBALogin'] 		=	0;
		$data['branch_id'] 			= '';
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$this->load->view('admin/login_with_otp', $data);
	}


	public function login()
	{
		$this->is_logged_in();
		//$this->load->view('admin/login');
		$data['branch_id'] = $this->uri->segment(4, 0);
		//tbl_cpwd_states
		$data['allowBALogin'] = 1;
		$this->load->view('admin/login_page', $data);
	}

	public function register()
	{

		$get_user_type = $this->customerM->selectData();
		$data['user_type'] = $get_user_type;
		// $sub = "Account verification notification";
		$get_countries = $this->customerM->get_Countries();
		$data['get_countries'] = $get_countries;
		if ($this->input->post()) {
			// print_r($this->input->post());die;
			$user_type_id	= $this->input->post('customer');
			// echo $user_type_id;die;
			if ($user_type_id == '1') {

				$name			= trim($this->input->post('form_1_name'));
				$email 			= trim($this->input->post('man_email1'));
				$branch_email 	= trim($this->input->post('branch_e_id'));
				$cid 			= trim($this->input->post('cid'));
				$user_official 			= $this->input->post('credit_email1');

				if (isset($_POST['g-recaptcha-response'])) {
					$captcha = $_POST['g-recaptcha-response'];
				}


				$this->form_validation->set_rules('form_1_name', ' Name', 'trim|required');
				$this->form_validation->set_rules('man_email1', 'email', 'trim|required');


				$secretKey = "6LewqQEVAAAAAKwJAIjVGPSnAxbQ2aZ4qNC1MuR2"; // for mng
				//$secretKey = "6Ldlfv4UAAAAAFC3s_x2gTiz0x-uN-XZEHYerwaw"; // for trendy
				//$secretKey = "6LcDK80UAAAAAGIVvhzUPukBzxAX5ojzOAU0NNbJ"; // for live
				$ip = $_SERVER['REMOTE_ADDR'];
				// post request to server
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($captcha) . '&remoteip=' . $ip;
				$response = file_get_contents($url);
				$responseKeys = json_decode($response, true);
				// should return JSON with success as true
				if ($responseKeys["success"]) {

					if ($this->form_validation->run() == TRUE) {

						// if($user_official == $branch_email) {
						// 	$branch_email_id = '';
						// } else {
						// 	$branch_email_id = $user_official;
						// }

						$data = array(
							'name'		 			=> $name,
							'email' 				=> $email,
							'user_type_id'  		=> $user_type_id,
							'user_category_id'  	=> 2,
							'user_type_status'		=> '2',
							'status'        		=> '1',
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'branch_email_id' 		=> $branch_email,
							'agree_terms_cond'		=> '1'
						);

						$save_data = $this->customerM->saveDetail($data);

						$team_id				= $this->input->post('team_no');
						$code 					= $this->input->post('code');
						$phone 					= $this->input->post('personal_no1');
						$cug_mobile 			= $this->input->post('cug_no1');
						$designation_id			= $this->input->post('designation');
						$desk_number 			= $this->input->post('desk_no');
						$board_number 			= $this->input->post('board_no');
						$team_no 				= $this->input->post('team_no');
						$data1 = array(
							'user_id'				=> $save_data,
							'team_id'				=> $team_id,
							'user_official_email' 	=> $user_official,
							'bank_id' 				=> $code,
							'phone'					=> $phone,
							'designation_id'		=> $designation_id,
							'cug_mobile'			=> $cug_mobile,
							'desk_number'			=> $desk_number,
							'board_number'      	=> $board_number,
							'team_id'               => $team_no,
							'status'        		=> '1',
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),

						);
						$save_option = $this->customerM->saveData($data1);

						if ($save_data > 0) {

							$designation_txt = $this->customerM->getDynamicTblData('bank_branch_head_category', array('id' => $designation_id));

							$branch_name = $this->customerM->getDynamicTblData('bank_branch_master', array('id' => $code));

							$message = '';
							$confirmUrl = site_url('admin/welcome/confirmEmail?user_id=' . base64_encode($save_data) . '&email=' . $user_official);

							$get_mail = $this->customerM->getOTPMail('2');

							$vars = array(
								'[$NAME]'  		=> ucwords($name),
								'[$URL]'  		=> $confirmUrl

							);

							$msg 		= strtr($get_mail['body'], $vars);
							$sub 		= $get_mail['subject'];

							$sign =  $msg;
							$sign .= $get_mail['content'];

							$this->sendEmail($user_official, $sign, $sub, $get_mail['send_to']);

							$get_mail1 = $this->customerM->getOTPMail('3');

							$vars1 = array(
								'[$Manager_NAME]'  		=> ucwords($name),
								'[$Designation]'  		=> $designation_txt['branch_head_category_name'],
								'[$Branch_Name]'  		=> $branch_name['branch_name'],
								'[$IFSC_Code]'  		=> $branch_name['ifsc_code'],
								'[$Manager_official_email_ID]'  		=> $email

							);

							$msg1 		= strtr($get_mail1['body'], $vars1);
							$sub2 		= $get_mail1['subject'];

							$sign2 =  $msg1;
							$sign2 .= $get_mail1['content'];

							/* send meail to her bank base on ifsc code */
							// $sub2 = "Bank Manager Notification";
							// $sign2 ='<html><body>Hello Sir,<br><p style="font-size:14px; margin-top:5px; margin-bottom:0px;">We are pleased to inform you that '.ucwords($name).' with the Position of Bank Manager is associated with your bank.</p><br><br><br><br>Thanks,<p style=" margin-top:5px; margin-bottom:0px;">Valuation Intelligence System</p></body></html>';
							$this->sendEmail($branch_email, $sign2, $sub2, $get_mail1['send_to']);

							$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin/welcome/success/1/' . $save_data);
						}
					} else {
						$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Your entered email id already exist.</div></div>';
						$this->session->set_flashdata('message', $message);
						redirect('Admin/Welcome/register');
					}
				} else {
					$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('Admin/Welcome/register');
				}
			} else if ($user_type_id == '2') {

				if (isset($_POST['g-recaptcha-response'])) {
					$captcha = $_POST['g-recaptcha-response'];
				}

				//$cid 						= trim($this->input->post('cid'));
				$name_type_2				= trim($this->input->post('name_type_2'));
				$email_type_2 				= trim($this->input->post('email_type_2'));
				$phone_type_2 				= trim($this->input->post('phone_type_2'));

				$alt_phone_type_2 			= trim($this->input->post('alt_phone_type_2'));
				$country_type2 				= $this->input->post('country_type2');
				$address_type_2 			= trim($this->input->post('address_type_2'));
				$states_type_2 				= $this->input->post('states_type_2');
				$cities_type_2 				= $this->input->post('cities_type_2');
				$pincode_type_2 			= trim($this->input->post('pincode_type_2'));

				$secretKey = "6LewqQEVAAAAAKwJAIjVGPSnAxbQ2aZ4qNC1MuR2"; // for mng
				//$secretKey = "6Ldlfv4UAAAAAFC3s_x2gTiz0x-uN-XZEHYerwaw"; // for trendy
				// $secretKey = "6LcDK80UAAAAAGIVvhzUPukBzxAX5ojzOAU0NNbJ"; // for live
				$ip = $_SERVER['REMOTE_ADDR'];
				// post request to server
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
				$response = file_get_contents($url);
				$responseKeys = json_decode($response, true);
				// should return JSON with success as true
				if ($responseKeys["success"]) {


					$data = array(
						'name'		 			=> $name_type_2,
						'email' 				=> $email_type_2,
						'user_type_id'  		=> $user_type_id,
						'user_category_id'  	=> 2,
						'user_type_status'		=> '2',
						'status'        		=> '1',
						'created_at'			=> date('Y-m-d H:i:s'),
						'updated_at'			=> date('Y-m-d H:i:s'),
						'agree_terms_cond'   	=> '1'
					);
					$save_data1 = $this->customerM->saveDetail($data);

					$data1 = array(
						'user_id'				=> $save_data1,
						'phone'					=> $phone_type_2,
						'cug_mobile'			=> $alt_phone_type_2,
						'pincode'				=> $pincode_type_2,
						'country_id'			=> $country_type2,
						'state_id'      		=> $states_type_2,
						'city_id'      			=> $cities_type_2,
						'address'				=> $address_type_2,
						'created_at'			=> date('Y-m-d H:i:s'),
						'updated_at'			=> date('Y-m-d H:i:s'),
						'status'        		=> '1'
					);

					$save_option = $this->customerM->saveData($data1);

					$confirmUrl = site_url('admin/welcome/confirmEmail?user_id=' . base64_encode($save_data1) . '&email=' . $email_type_2);

					$get_mail = $this->customerM->getOTPMail('2');

					$vars = array(
						'[$NAME]'  		=> ucwords($name_type_2),
						'[$URL]'  		=> $confirmUrl

					);

					$msg 		= strtr($get_mail['body'], $vars);
					$sub 		= $get_mail['subject'];

					$sign =  $msg;
					$sign .= $get_mail['content'];

					$this->sendEmail($email_type_2, $sign, $sub, $get_mail['send_to']);
					$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/success/2/' . $save_data1);
				} else {
					$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('Admin/Welcome/register');
				}
			} else if ($user_type_id == '3') {
				// print_r($this->input->post());die;


				if (isset($_POST['g-recaptcha-response'])) {
					$captcha = $_POST['g-recaptcha-response'];
				}

				//$cid 					= trim($this->input->post('cid'));
				$company_name			= trim($this->input->post('company_name'));
				$gstin 					= trim($this->input->post('gstin'));
				$cin_no 				= trim($this->input->post('cin_no'));
				$cor_country 			= $this->input->post('cor_country');
				$address_type_3 		= trim($this->input->post('address_type_3'));
				$cor_states 			= $this->input->post('cor_states');

				$pincode_type_3			= trim($this->input->post('pincode_type_3'));
				$cor_cities 			= $this->input->post('cor_cities');
				$board_num 				= trim($this->input->post('board_num'));
				$man_name_3 			= trim($this->input->post('man_name_3'));
				// $designation_type_3 	= trim($this->input->post('designation_type_3'));

				$man_email_3			= trim($this->input->post('man_email_3'));
				$comp_email_3 			= trim($this->input->post('comp_email_3'));
				$cug_no_3 				= trim($this->input->post('cug_no_3'));
				$personal_no_3 			= trim($this->input->post('personal_no_3'));
				$desk_phone_3 			= trim($this->input->post('desk_phone_3'));

				$checkk1				= trim($this->input->post('checkk1'));
				$rtype 					= $this->input->post('rtype');
				$country_com 			= $this->input->post('country_com');
				$address_com 			= trim($this->input->post('address_com'));
				$state_com 				= $this->input->post('state_com');

				$city_com 				= $this->input->post('city_com');
				$pincode_com 			= trim($this->input->post('pincode_com'));

				$secretKey = "6LewqQEVAAAAAKwJAIjVGPSnAxbQ2aZ4qNC1MuR2"; // for mng
				//$secretKey = "6Ldlfv4UAAAAAFC3s_x2gTiz0x-uN-XZEHYerwaw"; // for trendy
				// $secretKey = "6LcDK80UAAAAAGIVvhzUPukBzxAX5ojzOAU0NNbJ"; // for live
				$ip = $_SERVER['REMOTE_ADDR'];
				// post request to server
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
				$response = file_get_contents($url);
				$responseKeys = json_decode($response, true);
				// should return JSON with success as true
				if ($responseKeys["success"]) {

					$data = array(

						'name'		 	=> $man_name_3,
						'email' 		=> $man_email_3,
						'user_type_id'  => $user_type_id,
						'user_category_id' => 2,
						'user_type_status' => '2',
						'status'        => '1',
						'created_at'	=> date('Y-m-d H:i:s'),
						'updated_at'	=> date('Y-m-d H:i:s'),
						'agree_terms_cond'  => 1
					);
					$save_data1 = $this->customerM->saveDetail($data);

					if ($checkk1 == 2) {
						$data1 = array(
							'user_id'				=> $save_data1,
							'phone'					=> $personal_no_3,
							'cug_mobile'			=> $cug_no_3,
							'Desk_number'			=> $desk_phone_3,
							'Board_number'			=> $board_num,
							'pincode'				=> $pincode_type_3,
							'country_id'			=> $cor_country,
							'state_id'				=> $cor_states,
							'city_id'				=> $cor_cities,
							'address'				=> $address_type_3,
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'status'        		=> '1',
							'cin_no'				=> $cin_no,
							'company_name'			=> $company_name,
							'gstin_no'				=> $gstin,
							// 'designation_text'		=> $designation_type_3,
							'company_email'			=> $comp_email_3,
							'comp_addr_country'		=> $country_com,
							'comp_addr_state'		=> $state_com,
							'comp_addr_city'		=> $city_com,
							'comp_addr_address'		=> $address_com,
							'comp_addr_pincode'		=> $pincode_com,
							'comm_type'				=> $rtype

						);
					} else {
						$data1 = array(
							'user_id'				=> $save_data1,
							'phone'					=> $personal_no_3,
							'cug_mobile'			=> $cug_no_3,
							'Desk_number'			=> $desk_phone_3,
							'Board_number'			=> $board_num,
							'pincode'				=> $pincode_type_3,
							'country_id'			=> $cor_country,
							'state_id'				=> $cor_states,
							'city_id'				=> $cor_cities,
							'address'				=> $address_type_3,
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'status'        		=> '1',
							'company_name'			=> $company_name,
							'gstin_no'				=> $gstin,
							'cin_no'				=> $cin_no,
							// 'designation_text'		=> $designation_type_3,
							'company_email'			=> $comp_email_3,
							'comp_addr_country'		=> $cor_country,
							'comp_addr_state'		=> $cor_states,
							'comp_addr_city'		=> $cor_cities,
							'comp_addr_address'		=> $address_type_3,
							'comp_addr_pincode'		=> $pincode_type_3,
							'comm_type'				=> $rtype
						);
					}
					$save_option = $this->customerM->saveData($data1);


					$confirmUrl = site_url('admin/welcome/confirmEmail?user_id=' . base64_encode($save_data1) . '&email=' . $man_email_3);

					$get_mail = $this->customerM->getOTPMail('2');

					$vars = array(
						'[$NAME]'  		=> ucwords($man_name_3),
						'[$URL]'  		=> $confirmUrl

					);

					$msg 		= strtr($get_mail['body'], $vars);
					$sub 		= $get_mail['subject'];

					$sign =  $msg;
					$sign .= $get_mail['content'];


					$this->sendEmail($man_email_3, $sign, $sub, $get_mail['send_to']);

					$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/success/3/' . $save_data1);
				} else {
					$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('Admin/Welcome/register');
				}
			} else if ($user_type_id == '4') {

				if (isset($_POST['g-recaptcha-response'])) {
					$captcha = $_POST['g-recaptcha-response'];
				}

				$name_type_4				= trim($this->input->post('name_type_2'));
				$email_type_4 				= trim($this->input->post('email_type_2'));
				$phone_type_4 				= trim($this->input->post('phone_type_2'));
				$alt_phone_type_4 			= trim($this->input->post('alt_phone_type_2'));
				$country_type4 				= trim($this->input->post('country_type2'));
				$address_type_4 			= trim($this->input->post('address_type_2'));
				$states_type_4 				= trim($this->input->post('states_type_2'));
				$cities_type_4 				= trim($this->input->post('cities_type_2'));
				$pincode_type_4 			= trim($this->input->post('pincode_type_2'));

				$secretKey = "6LewqQEVAAAAAKwJAIjVGPSnAxbQ2aZ4qNC1MuR2"; // for mng
				//$secretKey = "6Ldlfv4UAAAAAFC3s_x2gTiz0x-uN-XZEHYerwaw"; // for trendy
				// $secretKey = "6LcDK80UAAAAAGIVvhzUPukBzxAX5ojzOAU0NNbJ"; // for live
				$ip = $_SERVER['REMOTE_ADDR'];
				// post request to server
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
				$response = file_get_contents($url);
				$responseKeys = json_decode($response, true);
				// should return JSON with success as true
				if ($responseKeys["success"]) {


					$data = array(
						'name'		 			=> $name_type_4,
						'email' 				=> $email_type_4,
						'user_type_id'  		=> $user_type_id,
						'user_category_id'  	=> 2,
						'user_type_status'		=> '2',
						'status'        		=> '1',
						'created_at'			=> date('Y-m-d H:i:s'),
						'updated_at'			=> date('Y-m-d H:i:s'),
						'agree_terms_cond'   	=> '1'
					);
					$save_data1 = $this->customerM->saveDetail($data);

					$data1 = array(
						'user_id'				=> $save_data1,
						'phone'					=> $phone_type_4,
						'cug_mobile'			=> $alt_phone_type_4,
						'pincode'				=> $pincode_type_4,
						'country_id'			=> $country_type4,
						'state_id'      		=> $states_type_4,
						'city_id'      			=> $cities_type_4,
						'address'				=> $address_type_4,
						'created_at'			=> date('Y-m-d H:i:s'),
						'updated_at'			=> date('Y-m-d H:i:s'),
						'status'        		=> '1'
					);

					$save_option = $this->customerM->saveData($data1);

					$confirmUrl = site_url('admin/welcome/confirmEmail?user_id=' . base64_encode($save_data1) . '&email=' . $email_type_4);

					$get_mail = $this->customerM->getOTPMail('2');

					$vars = array(
						'[$NAME]'  		=> ucwords($name_type_4),
						'[$URL]'  		=> $confirmUrl
					);
					$msg 		= strtr($get_mail['body'], $vars);
					$sub 		= $get_mail['subject'];

					$sign =  $msg;
					$sign .= $get_mail['content'];

					$this->sendEmail($email_type_4, $sign, $sub, $get_mail['send_to']);

					// echo $save_data1;

					$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/success/4/' . $save_data1);
				} else {
					$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('Admin/Welcome/register');
				}
			} else if ($user_type_id == '5') {

				if (isset($_POST['g-recaptcha-response'])) {
					$captcha = $_POST['g-recaptcha-response'];
				}

				//$cid 						= trim($this->input->post('cid'));
				$professional_type				= trim($this->input->post('professional_type'));
				$name_professional 				= trim($this->input->post('name_professional'));
				$registration_no 				= trim($this->input->post('registration_no'));

				$ipa_enrolled_name 				= trim($this->input->post('ipa_enrolled_name'));
				$regulation_registered 			= $this->input->post('regulation_registered');
				$insolvency_country 			= $this->input->post('insolvency_country');
				$insolvency_states 				= $this->input->post('insolvency_states');
				$insolvency_cities 				= $this->input->post('insolvency_cities');
				$insolvency_address 			= trim($this->input->post('insolvency_address'));

				$insolvency_pincode 			= trim($this->input->post('insolvency_pincode'));
				$insolvency_mobile_number 		= $this->input->post('insolvency_mobile_number');
				$insolvency_phone_number 		= $this->input->post('insolvency_phone_number');
				$insolvency_email 				= $this->input->post('insolvency_email');
				$insolvency_assistant_name 		= $this->input->post('insolvency_assistant_name');
				$insolvency_assistant_mobile 	= trim($this->input->post('insolvency_assistant_mobile'));
				$insolvency_assistant_email 	= trim($this->input->post('insolvency_assistant_email'));


				$name_professional_entity 		= trim($this->input->post('name_professional_entity'));
				$registration_no_entity 		= trim($this->input->post('registration_no_entity'));

				$ipe_constitution 				= trim($this->input->post('ipe_constitution'));
				$entity_country 				= $this->input->post('entity_country');
				$entity_address 				= trim($this->input->post('entity_address'));
				$entity_states 					= $this->input->post('entity_states');
				$entity_cities 					= $this->input->post('entity_cities');
				$entity_pincode 				= trim($this->input->post('entity_pincode'));

				$entity_mobile_number 			= trim($this->input->post('entity_mobile_number'));
				$entity_phone_number 			= $this->input->post('entity_phone_number');
				$entity_email 					= $this->input->post('entity_email');
				$key_person_name 				= $this->input->post('key_person_name');
				$key_person_mobile 				= $this->input->post('key_person_mobile');
				$key_person_email 				= trim($this->input->post('key_person_email'));
				$designation 					= trim($this->input->post('designation'));

				$director_name_n 				= implode(',', $this->input->post('director_name_n'));

				$secretKey = "6LewqQEVAAAAAKwJAIjVGPSnAxbQ2aZ4qNC1MuR2"; // for mng
				//$secretKey = "6Ldlfv4UAAAAAFC3s_x2gTiz0x-uN-XZEHYerwaw"; // for trendy
				// $secretKey = "6LcDK80UAAAAAGIVvhzUPukBzxAX5ojzOAU0NNbJ"; // for live
				$ip = $_SERVER['REMOTE_ADDR'];
				// post request to server
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
				$response = file_get_contents($url);
				$responseKeys = json_decode($response, true);
				// should return JSON with success as true
				if ($responseKeys["success"]) {

					if ($professional_type == 1) {
						$data = array(
							'name'		 			=> $name_professional,
							'email' 				=> $insolvency_email,
							'user_type_id'  		=> $user_type_id,
							'user_category_id'  	=> 2,
							'user_type_status'		=> '2',
							'status'        		=> '1',
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'insolvency_professional_type' 				=> $professional_type,
							'agree_terms_cond'   	=> '1'
						);
					} else {
						$data = array(
							'name'		 			=> $name_professional_entity,
							'email' 				=> $entity_email,
							'user_type_id'  		=> $user_type_id,
							'user_category_id'  	=> 2,
							'user_type_status'		=> '2',
							'status'        		=> '1',
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'insolvency_professional_type' 				=> $professional_type,
							'agree_terms_cond'   	=> '1'
						);
					}



					$save_data1 = $this->customerM->saveDetail($data);

					if ($professional_type == 1) {
						$data1 = array(
							'user_id'				=> $save_data1,
							'phone'					=> $insolvency_phone_number,
							'cug_mobile'			=> $insolvency_mobile_number,
							'pincode'				=> $insolvency_pincode,
							'country_id'			=> $insolvency_country,
							'state_id'      		=> $insolvency_states,
							'city_id'      			=> $insolvency_cities,
							'address'				=> $insolvency_address,
							'registration_no'		=> $registration_no,
							'enrolled_ipa'			=> $ipa_enrolled_name,
							'constitution_ipe'		=> '',
							'regulation_registered'		=> $regulation_registered,
							'assistant_name'			=> $insolvency_assistant_name,
							'assistant_mobile'			=> $insolvency_assistant_mobile,
							'assistant_email'			=> $insolvency_assistant_email,
							'designation'			=> '',
							'director_name'			=> '',
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'status'        		=> '1'
						);
					} else {
						$data1 = array(
							'user_id'				=> $save_data1,
							'phone'					=> $entity_phone_number,
							'cug_mobile'			=> $entity_mobile_number,
							'pincode'				=> $entity_pincode,
							'country_id'			=> $entity_country,
							'state_id'      		=> $entity_states,
							'city_id'      			=> $entity_cities,
							'address'				=> $entity_address,
							'registration_no'		=> $registration_no_entity,
							'enrolled_ipa'			=> '',
							'constitution_ipe'		=> $ipe_constitution,
							'regulation_registered'		=> '',
							'assistant_name'			=> $key_person_name,
							'assistant_mobile'			=> $key_person_mobile,
							'assistant_email'			=> $key_person_email,
							'designation'			=> $designation,
							'director_name'			=> $director_name_n,
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'status'        		=> '1'
						);
					}



					$save_option = $this->customerM->saveData($data1);


					$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/success/5/' . $save_data1);
				} else {
					$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('Admin/Welcome/register');
				}
			} else if ($user_type_id == '6') {
				// print_r($this->input->post());die;


				if (isset($_POST['g-recaptcha-response'])) {
					$captcha = $_POST['g-recaptcha-response'];
				}

				//$cid 					= trim($this->input->post('cid'));
				$company_name			= trim($this->input->post('company_name'));
				$gstin 					= trim($this->input->post('gstin'));
				$cin_no 				= trim($this->input->post('cin_no'));
				$cor_country 			= $this->input->post('cor_country');
				$address_type_3 		= trim($this->input->post('address_type_3'));
				$cor_states 			= $this->input->post('cor_states');

				$pincode_type_3			= trim($this->input->post('pincode_type_3'));
				$cor_cities 			= $this->input->post('cor_cities');
				$board_num 				= trim($this->input->post('board_num'));
				$man_name_3 			= trim($this->input->post('man_name_3'));
				// $designation_type_3 	= trim($this->input->post('designation_type_3'));

				$man_email_3			= trim($this->input->post('man_email_3'));
				$comp_email_3 			= trim($this->input->post('comp_email_3'));
				$cug_no_3 				= trim($this->input->post('cug_no_3'));
				$personal_no_3 			= trim($this->input->post('personal_no_3'));
				$desk_phone_3 			= trim($this->input->post('desk_phone_3'));

				$checkk1				= trim($this->input->post('checkk1'));
				$rtype 					= $this->input->post('rtype');
				$country_com 			= $this->input->post('country_com');
				$address_com 			= trim($this->input->post('address_com'));
				$state_com 				= $this->input->post('state_com');

				$city_com 				= $this->input->post('city_com');
				$pincode_com 			= trim($this->input->post('pincode_com'));

				$secretKey = "6LewqQEVAAAAAKwJAIjVGPSnAxbQ2aZ4qNC1MuR2"; // for mng
				//$secretKey = "6Ldlfv4UAAAAAFC3s_x2gTiz0x-uN-XZEHYerwaw"; // for trendy
				// $secretKey = "6LcDK80UAAAAAGIVvhzUPukBzxAX5ojzOAU0NNbJ"; // for live
				$ip = $_SERVER['REMOTE_ADDR'];
				// post request to server
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
				$response = file_get_contents($url);
				$responseKeys = json_decode($response, true);
				// should return JSON with success as true
				if ($responseKeys["success"]) {

					$data = array(

						'name'		 	=> $man_name_3,
						'email' 		=> $man_email_3,
						'user_type_id'  => $user_type_id,
						'user_category_id' => 2,
						'user_type_status' => '2',
						'status'        => '1',
						'created_at'	=> date('Y-m-d H:i:s'),
						'updated_at'	=> date('Y-m-d H:i:s'),
						'agree_terms_cond'  => 1
					);
					$save_data1 = $this->customerM->saveDetail($data);

					if ($checkk1 == 2) {
						$data1 = array(
							'user_id'				=> $save_data1,
							'phone'					=> $personal_no_3,
							'cug_mobile'			=> $cug_no_3,
							'Desk_number'			=> $desk_phone_3,
							'Board_number'			=> $board_num,
							'pincode'				=> $pincode_type_3,
							'country_id'			=> $cor_country,
							'state_id'				=> $cor_states,
							'city_id'				=> $cor_cities,
							'address'				=> $address_type_3,
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'status'        		=> '1',
							'cin_no'				=> $cin_no,
							'company_name'			=> $company_name,
							'gstin_no'				=> $gstin,
							// 'designation_text'		=> $designation_type_3,
							'company_email'			=> $comp_email_3,
							'comp_addr_country'		=> $country_com,
							'comp_addr_state'		=> $state_com,
							'comp_addr_city'		=> $city_com,
							'comp_addr_address'		=> $address_com,
							'comp_addr_pincode'		=> $pincode_com,
							'comm_type'				=> $rtype

						);
					} else {
						$data1 = array(
							'user_id'				=> $save_data1,
							'phone'					=> $personal_no_3,
							'cug_mobile'			=> $cug_no_3,
							'Desk_number'			=> $desk_phone_3,
							'Board_number'			=> $board_num,
							'pincode'				=> $pincode_type_3,
							'country_id'			=> $cor_country,
							'state_id'				=> $cor_states,
							'city_id'				=> $cor_cities,
							'address'				=> $address_type_3,
							'created_at'			=> date('Y-m-d H:i:s'),
							'updated_at'			=> date('Y-m-d H:i:s'),
							'status'        		=> '1',
							'company_name'			=> $company_name,
							'gstin_no'				=> $gstin,
							'cin_no'				=> $cin_no,
							// 'designation_text'		=> $designation_type_3,
							'company_email'			=> $comp_email_3,
							'comp_addr_country'		=> $cor_country,
							'comp_addr_state'		=> $cor_states,
							'comp_addr_city'		=> $cor_cities,
							'comp_addr_address'		=> $address_type_3,
							'comp_addr_pincode'		=> $pincode_type_3,
							'comm_type'				=> $rtype
						);
					}
					$save_option = $this->customerM->saveData($data1);


					$confirmUrl = site_url('admin/welcome/confirmEmail?user_id=' . base64_encode($save_data1) . '&email=' . $man_email_3);

					$get_mail = $this->customerM->getOTPMail('2');

					$vars = array(
						'[$NAME]'  		=> ucwords($man_name_3),
						'[$URL]'  		=> $confirmUrl

					);

					$msg 		= strtr($get_mail['body'], $vars);
					$sub 		= $get_mail['subject'];

					$sign =  $msg;
					$sign .= $get_mail['content'];


					$this->sendEmail($man_email_3, $sign, $sub, $get_mail['send_to']);

					$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/success/6/' . $save_data1);
				} else {
					$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
					$this->session->set_flashdata('message', $message);
					redirect('Admin/Welcome/register');
				}
			}
		} else {
			$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Invalid Captcha.</div></div>';
			$this->session->set_flashdata('message', $message);
			redirect('Admin/Welcome/register');
			//$this->load->view('welcome/showCustomer', $data);
		}
	}

	public function  update_password()
	{
		//print_r($_POST);die;


		$this->db->update("users", array('password' => $_POST['password']), array('email' => $_POST['email']));
		$this->session->set_flashdata('success', 'Password Add Succcessfully !');
		redirect(site_url() . 'welcome/set_password');
	}


	public function set_password()
	{
		$this->is_logged_in();
		$data['allowBALogin'] 		=	0;
		$data['branch_id'] 			= '';
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$this->load->view('admin/set_password', $data);
	}



	// $this->db->update("users", array('password'=>$_POST['password']), array('email'=>$_POST['email']));
	//     	    $this->session->set_flashdata('success', 'Password Add Succcessfully !');
	//         	redirect(site_url().'welcome/set_password');











	public function  login_with_password()
	{
		$user_login = array(

			'email' => $this->input->post('email'),
			'password' => md5($this->input->post('password'))

		);

		// print_r($user_login);
		// die();

		$result = $this->db->get_where('users',  $user_login)->row_array();
		if (!empty($result)) {
			$this->session->set_userdata('AdminData', $result);
			// redirect(site_url().'Admin/Welcome/dashboard');

			echo "hello";
		} else {
			// $this->session->set_flashdata('adminnotlogin',"Username or Password is not Correct. Try Again...");
			// $this->session->set_flashdata('msg_class','alert-danger');
			// redirect(site_url().'welcome/set_password');
			echo "not";
		}
	}

	/* check email for with password validation */
	public function checkMailupdatePassword()
	{
		$this->is_logged_in();

		if ($this->input->post()) {
			$allowBALogin = $this->input->post('allowBALogin');
			$branch_id = $this->input->post('branch_id');
			$email = $this->input->post('email');
			$type = $this->input->post('type');
			$chk_email = $this->customerM->checkEmailForAdmin($email);
			$user_name = $chk_email['name'];
			if ($chk_email != 2) {
				$user_id = $chk_email['id'];
				if ($chk_email['user_type_id'] > 6) {

					$spndData =  $this->customerM->checkSuspendForAdmin($user_id, $chk_email['user_category_id']);
					if ($spndData > 0) {
						$trmData =  $this->customerM->checkTerminationForAdmin($user_id, $chk_email['user_category_id']);
						if ($trmData > 0) {
							$user_id = $chk_email['id'];
							$data['allowBALogin'] = $allowBALogin;
							$data['branch_id'] = $branch_id;
							if ($allowBALogin == 1) {
								$chk_if_BA = $this->customerM->checkIfBA($user_id, $chk_email['user_category_id']);
								if (!empty($chk_if_BA)) {
									$this->form_validation->set_rules('email', 'email', 'trim|required');
									$otp_code = mt_rand(100000, 999999);

									if ($this->form_validation->run() == TRUE) {
										if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
											$cbc_det = $this->db
												->select('*')
												->from('user_details')
												->where(array('user_id' => $user_id))
												->get()
												->row_array();
											$send_email = $cbc_det['user_official_email'];
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 1;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
											$data['ifsc'] = $branch_ifsc;
										} else {
											$send_email = $email;
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 0;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$data['ifsc'] = '';
										}

										$get_mail = $this->customerM->getOTPMail('1');

										$vars = array(
											'[$USER_NAME]'  => ucwords($user_name),
											'[$OTP_CODE]'  	=> ucwords($otp_code)

										);
										$msg 		= strtr($get_mail['body'], $vars);
										$sub 		= $get_mail['subject'];

										$sign =  $msg;
										$sign .= $get_mail['content'];



										$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
										$data['email'] = $user_id;
										$data['email_id'] = $email;
										$data['otp'] = $otp_code;

										if ($chk_email['user_category_id'] != 3) {
											$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
										} else {
											$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
										}

										$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

										$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
										$this->session->set_flashdata('message', $message);
										//redirect('Admin/Welcome/otpCode', $data);

										//$this->load->view('admin/otp_cod',  $data);
										$this->load->view('admin/update_password',  $data);
									}
								} else {
									$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> You are not authorised for this activity. Kindly contact our Administration at Mob. No/ Email ID.</div>';
									$this->session->set_flashdata('message', $message);
									redirect('admin/welcome/login');
								}
							} else {
								$this->form_validation->set_rules('email', 'email', 'trim|required');
								$otp_code = mt_rand(100000, 999999);
								if ($this->form_validation->run() == TRUE) {
									if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
										$cbc_det = $this->db
											->select('*')
											->from('user_details')
											->where(array('user_id' => $user_id))
											->get()
											->row_array();
										$send_email = $cbc_det['user_official_email'];
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 1;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
										$data['ifsc'] = $branch_ifsc;
									} else {
										$send_email = $email;
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 0;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$data['ifsc'] = '';
									}

									$get_mail = $this->customerM->getOTPMail('1');

									$vars = array(
										'[$USER_NAME]'  => ucwords($user_name),
										'[$OTP_CODE]'  	=> ucwords($otp_code)

									);

									$msg 		= strtr($get_mail['body'], $vars);
									$sub 		= $get_mail['subject'];

									$sign =  $msg;
									$sign .= $get_mail['content'];



									$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
									$data['email'] = $user_id;
									$data['email_id'] = $email;
									$data['otp'] = $otp_code;

									if ($chk_email['user_category_id'] != 3) {
										$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
									} else {
										$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
									}

									$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

									$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
									$this->session->set_flashdata('message', $message);
									//redirect('Admin/Welcome/otpCode', $data);

									//$this->load->view('admin/otp_cod',  $data);
									$this->load->view('admin/update_password',  $data);
								}
							}
						} else {
							$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been terminated.Please contact VIS Admin.</div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin');
						}
					} else {
						$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been suspended.Please contact VIS Admin.</div>';
						$this->session->set_flashdata('message', $message);
						redirect('admin');
					}
				} else {
					$spndData =  $this->customerM->checkSuspendForAdmin($user_id, $chk_email['user_category_id']);
					if ($spndData > 0) {
						$trmData =  $this->customerM->checkTerminationForAdmin($user_id, $chk_email['user_category_id']);
						if ($trmData > 0) {
							$user_id = $chk_email['id'];
							$data['allowBALogin'] = $allowBALogin;
							$data['branch_id'] = $branch_id;
							if ($allowBALogin == 1) {
								$chk_if_BA = $this->customerM->checkIfBA($user_id, $chk_email['user_category_id']);
								if (!empty($chk_if_BA)) {
									$this->form_validation->set_rules('email', 'email', 'trim|required');
									$otp_code = mt_rand(100000, 999999);

									if ($this->form_validation->run() == TRUE) {
										if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
											$cbc_det = $this->db
												->select('*')
												->from('user_details')
												->where(array('user_id' => $user_id))
												->get()
												->row_array();
											$send_email = $cbc_det['user_official_email'];
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 1;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
											$data['ifsc'] = $branch_ifsc;
										} else {
											$send_email = $email;
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 0;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$data['ifsc'] = '';
										}

										$get_mail = $this->customerM->getOTPMail('1');

										$vars = array(
											'[$USER_NAME]'  => ucwords($user_name),
											'[$OTP_CODE]'  	=> ucwords($otp_code)

										);
										$msg 		= strtr($get_mail['body'], $vars);
										$sub 		= $get_mail['subject'];

										$sign =  $msg;
										$sign .= $get_mail['content'];



										$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
										$data['email'] = $user_id;
										$data['email_id'] = $email;
										$data['otp'] = $otp_code;

										if ($chk_email['user_category_id'] != 3) {
											$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
										} else {
											$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
										}

										$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

										$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
										$this->session->set_flashdata('message', $message);
										//redirect('Admin/Welcome/otpCode', $data);

										//$this->load->view('admin/otp_cod',  $data);
										$this->load->view('admin/update_password',  $data);
									}
								} else {
									$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> You are not authorised for this activity. Kindly contact our Administration at Mob. No/ Email ID.</div>';
									$this->session->set_flashdata('message', $message);
									redirect('admin/welcome/login');
								}
							} else {
								$this->form_validation->set_rules('email', 'email', 'trim|required');
								$otp_code = mt_rand(100000, 999999);

								if ($this->form_validation->run() == TRUE) {
									if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
										$cbc_det = $this->db
											->select('*')
											->from('user_details')
											->where(array('user_id' => $user_id))
											->get()
											->row_array();
										$send_email = $cbc_det['user_official_email'];
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 1;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
										$data['ifsc'] = $branch_ifsc;
									} else {
										$send_email = $email;
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 0;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$data['ifsc'] = '';
									}

									$get_mail = $this->customerM->getOTPMail('1');

									$vars = array(
										'[$USER_NAME]'  => ucwords($user_name),
										'[$OTP_CODE]'  	=> ucwords($otp_code)

									);

									$msg 		= strtr($get_mail['body'], $vars);
									$sub 		= $get_mail['subject'];

									$sign =  $msg;
									$sign .= $get_mail['content'];



									$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
									$data['email'] = $user_id;
									$data['email_id'] = $email;
									$data['otp'] = $otp_code;

									if ($chk_email['user_category_id'] != 3) {
										$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
									} else {
										$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
									}

									$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

									$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
									$this->session->set_flashdata('message', $message);
									//redirect('Admin/Welcome/otpCode', $data);

									//$this->load->view('admin/otp_cod',  $data);
									$this->load->view('admin/update_password',  $data);
								}
							}
						} else {
							$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been terminated.Please contact VIS Admin.</div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin');
						}
					} else {
						$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been suspended.Please contact VIS Admin.</div>';
						$this->session->set_flashdata('message', $message);
						redirect('admin');
					}
				}
			} else if ($chk_email == 2) {
				if ($allowBALogin == 1) {
					$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Entered Email Id is not registered.</div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/login');
				} else {
					$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Entered Email Id is not registered.</div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome');
				}
			}
		} else {
			redirect('admin/welcome');
		}
	}



	/* check email for with password validation */
	public function checkMailPassword()
	{
		$this->is_logged_in();

		if ($this->input->post()) {
			$allowBALogin = $this->input->post('allowBALogin');
			$branch_id = $this->input->post('branch_id');
			$email = $this->input->post('email');
			$type = $this->input->post('type');
			$chk_email = $this->customerM->checkEmailForAdmin($email);
			$user_name = $chk_email['name'];
			if ($chk_email != 2) {
				$user_id = $chk_email['id'];
				echo "<script>alert(".$chk_email['user_type_id'].");</script>";
				if ($chk_email['user_type_id'] > 6) {

					$spndData =  $this->customerM->checkSuspendForAdmin($user_id, $chk_email['user_category_id']);
					if ($spndData > 0) {
						$trmData =  $this->customerM->checkTerminationForAdmin($user_id, $chk_email['user_category_id']);
						if ($trmData > 0) {
							$user_id = $chk_email['id'];
							$data['allowBALogin'] = $allowBALogin;
							$data['branch_id'] = $branch_id;
							if ($allowBALogin == 1) {
								$chk_if_BA = $this->customerM->checkIfBA($user_id, $chk_email['user_category_id']);
								if (!empty($chk_if_BA)) {
									$this->form_validation->set_rules('email', 'email', 'trim|required');
									$otp_code = mt_rand(100000, 999999);

									if ($this->form_validation->run() == TRUE) {
										if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
											$cbc_det = $this->db
												->select('*')
												->from('user_details')
												->where(array('user_id' => $user_id))
												->get()
												->row_array();
											$send_email = $cbc_det['user_official_email'];
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 1;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
											$data['ifsc'] = $branch_ifsc;
										} else {
											$send_email = $email;
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 0;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$data['ifsc'] = '';
										}

										$get_mail = $this->customerM->getOTPMail('1');

										$vars = array(
											'[$USER_NAME]'  => ucwords($user_name),
											'[$OTP_CODE]'  	=> ucwords($otp_code)

										);
										$msg 		= strtr($get_mail['body'], $vars);
										$sub 		= $get_mail['subject'];

										$sign =  $msg;
										$sign .= $get_mail['content'];



										$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
										$data['email'] = $user_id;
										$data['email_id'] = $email;
										$data['otp'] = $otp_code;

										if ($chk_email['user_category_id'] != 3) {
											$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
										} else {
											$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
										}

										$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

										$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
										$this->session->set_flashdata('message', $message);
										//redirect('Admin/Welcome/otpCode', $data);

										//$this->load->view('admin/otp_cod',  $data);
										$this->load->view('admin/password_login',  $data);
									} 
									// else {
									// 	$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Please enter valid email.</div>';
									// 	$this->session->set_flashdata('message', $message);
									// 	redirect('admin/welcome/index');
									// }
								} else {
									$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> You are not authorised for this activity. Kindly contact our Administration at Mob. No/ Email ID.</div>';
									$this->session->set_flashdata('message', $message);
									redirect('admin/welcome/login');
								}
							} else {
								$this->form_validation->set_rules('email', 'email', 'trim|required');
								$otp_code = mt_rand(100000, 999999);
								if ($this->form_validation->run() == TRUE) {
									if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
										$cbc_det = $this->db
											->select('*')
											->from('user_details')
											->where(array('user_id' => $user_id))
											->get()
											->row_array();
										$send_email = $cbc_det['user_official_email'];
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 1;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
										$data['ifsc'] = $branch_ifsc;
									} else {
										$send_email = $email;
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 0;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$data['ifsc'] = '';
									}

									$get_mail = $this->customerM->getOTPMail('1');

									$vars = array(
										'[$USER_NAME]'  => ucwords($user_name),
										'[$OTP_CODE]'  	=> ucwords($otp_code)

									);

									$msg 		= strtr($get_mail['body'], $vars);
									$sub 		= $get_mail['subject'];

									$sign =  $msg;
									$sign .= $get_mail['content'];



									$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
									$data['email'] = $user_id;
									$data['email_id'] = $email;
									$data['otp'] = $otp_code;

									if ($chk_email['user_category_id'] != 3) {
										$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
									} else {
										$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
									}

									$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

									$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
									$this->session->set_flashdata('message', $message);
									//redirect('Admin/Welcome/otpCode', $data);

									//$this->load->view('admin/otp_cod',  $data);
									$this->load->view('admin/password_login',  $data);
								}
							}
						} else {
							$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been terminated.Please contact VIS Admin.</div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin');
						}
					} else {
						$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been suspended.Please contact VIS Admin.</div>';
						$this->session->set_flashdata('message', $message);
						redirect('admin');
					}
				} else {
					$spndData =  $this->customerM->checkSuspendForAdmin($user_id, $chk_email['user_category_id']);
					if ($spndData > 0) {
						$trmData =  $this->customerM->checkTerminationForAdmin($user_id, $chk_email['user_category_id']);
						if ($trmData > 0) {
							$user_id = $chk_email['id'];
							$data['allowBALogin'] = $allowBALogin;
							$data['branch_id'] = $branch_id;
							if ($allowBALogin == 1) {
								$chk_if_BA = $this->customerM->checkIfBA($user_id, $chk_email['user_category_id']);
								if (!empty($chk_if_BA)) {
									$this->form_validation->set_rules('email', 'Email', 'trim|required');
									$otp_code = mt_rand(100000, 999999);

									if ($this->form_validation->run() == TRUE) {
										if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
											$cbc_det = $this->db
												->select('*')
												->from('user_details')
												->where(array('user_id' => $user_id))
												->get()
												->row_array();
											$send_email = $cbc_det['user_official_email'];
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 1;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
											$data['ifsc'] = $branch_ifsc;
										} else {
											$send_email = $email;
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 0;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$data['ifsc'] = '';
										}

										$get_mail = $this->customerM->getOTPMail('1');

										$vars = array(
											'[$USER_NAME]'  => ucwords($user_name),
											'[$OTP_CODE]'  	=> ucwords($otp_code)

										);
										$msg 		= strtr($get_mail['body'], $vars);
										$sub 		= $get_mail['subject'];

										$sign =  $msg;
										$sign .= $get_mail['content'];

										$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
										$data['email'] = $user_id;
										$data['email_id'] = $email;
										$data['otp'] = $otp_code;

										if ($chk_email['user_category_id'] != 3) {
											$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
										} else {
											$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
										}

										$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

										$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
										$this->session->set_flashdata('message', $message);
										//redirect('Admin/Welcome/otpCode', $data);

										//$this->load->view('admin/otp_cod',  $data);
										$this->load->view('admin/password_login',  $data);
									}
								} else {
									$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> You are not authorised for this activity. Kindly contact our Administration at Mob. No/ Email ID.</div>';
									$this->session->set_flashdata('message', $message);
									redirect('admin/welcome/login');
								}
							} else {
								$this->form_validation->set_rules('email', 'email', 'trim|required');
								$otp_code = mt_rand(100000, 999999);

								if ($this->form_validation->run() == TRUE) {
									if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
										$cbc_det = $this->db
											->select('*')
											->from('user_details')
											->where(array('user_id' => $user_id))
											->get()
											->row_array();
										$send_email = $cbc_det['user_official_email'];
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 1;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
										$data['ifsc'] = $branch_ifsc;
									} else {
										$send_email = $email;
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 0;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$data['ifsc'] = '';
									}

									$get_mail = $this->customerM->getOTPMail('1');

									$vars = array(
										'[$USER_NAME]'  => ucwords($user_name),
										'[$OTP_CODE]'  	=> ucwords($otp_code)

									);

									$msg 		= strtr($get_mail['body'], $vars);
									$sub 		= $get_mail['subject'];

									$sign =  $msg;
									$sign .= $get_mail['content'];



									$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
									$data['email'] = $user_id;
									$data['email_id'] = $email;
									$data['otp'] = $otp_code;

									if ($chk_email['user_category_id'] != 3) {
										$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
									} else {
										$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
									}

									$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

									$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
									$this->session->set_flashdata('message', $message);
									//redirect('Admin/Welcome/otpCode', $data);

									//$this->load->view('admin/otp_cod',  $data);
									$this->load->view('admin/password_login',  $data);
								}
							}
						} else {
							$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been terminated.Please contact VIS Admin.</div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin');
						}
					} else {
						$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been suspended.Please contact VIS Admin.</div>';
						$this->session->set_flashdata('message', $message);
						redirect('admin');
					}
				}
			} else if ($chk_email == 2) {
				if ($allowBALogin == 1) {
					$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Entered Email Id is not registered.</div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/login');
				} else {
					$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Entered Email Id is not registered.</div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome');
				}
			}
		} else {
			redirect('admin/welcome');
		}
	}

/**
 * Create a function over shere and define Login mechanism
 * @coder74
 */
// public function (Type $var = null)
// {
// 	# code...
// }


	/* check email for validation */
	public function checkMail()
	{
		$this->is_logged_in();

		if ($this->input->post()) {
			$allowBALogin = $this->input->post('allowBALogin');
			$branch_id = $this->input->post('branch_id');
			$email = $this->input->post('email');
			$type = $this->input->post('type');
			$chk_email = $this->customerM->checkEmailForAdmin($email);
			$user_name = $chk_email['name'];
			if ($chk_email != 2) {
				$user_id = $chk_email['id'];
				if ($chk_email['user_type_id'] > 6) {

					$spndData =  $this->customerM->checkSuspendForAdmin($user_id, $chk_email['user_category_id']);
					if ($spndData > 0) {
						$trmData =  $this->customerM->checkTerminationForAdmin($user_id, $chk_email['user_category_id']);
						if ($trmData > 0) {
							$user_id = $chk_email['id'];
							$data['allowBALogin'] = $allowBALogin;
							$data['branch_id'] = $branch_id;
							if ($allowBALogin == 1) {
								$chk_if_BA = $this->customerM->checkIfBA($user_id, $chk_email['user_category_id']);
								if (!empty($chk_if_BA)) {
									$this->form_validation->set_rules('email', 'email', 'trim|required');
									$otp_code = mt_rand(100000, 999999);

									if ($this->form_validation->run() == TRUE) {
										if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
											$cbc_det = $this->db
												->select('*')
												->from('user_details')
												->where(array('user_id' => $user_id))
												->get()
												->row_array();
											$send_email = $cbc_det['user_official_email'];
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 1;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
											$data['ifsc'] = $branch_ifsc;
										} else {
											$send_email = $email;
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 0;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$data['ifsc'] = '';
										}

										$get_mail = $this->customerM->getOTPMail('1');

										$vars = array(
											'[$USER_NAME]'  => ucwords($user_name),
											'[$OTP_CODE]'  	=> ucwords($otp_code)

										);
										$msg 		= strtr($get_mail['body'], $vars);
										$sub 		= $get_mail['subject'];

										$sign =  $msg;
										$sign .= $get_mail['content'];



										$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
										$data['email'] = $user_id;
										$data['email_id'] = $email;
										$data['otp'] = $otp_code;

										if ($chk_email['user_category_id'] != 3) {
											$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
										} else {
											$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
										}

										$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

										$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
										$this->session->set_flashdata('message', $message);
										//redirect('Admin/Welcome/otpCode', $data);

										//$this->load->view('admin/otp_cod',  $data);
										$this->load->view('admin/send_otp',  $data);
									}
								} else {
									$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> You are not authorised for this activity. Kindly contact our Administration at Mob. No/ Email ID.</div>';
									$this->session->set_flashdata('message', $message);
									redirect('admin/welcome/login');
								}
							} else {
								$this->form_validation->set_rules('email', 'email', 'trim|required');
								$otp_code = mt_rand(100000, 999999);
								if ($this->form_validation->run() == TRUE) {
									if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
										$cbc_det = $this->db
											->select('*')
											->from('user_details')
											->where(array('user_id' => $user_id))
											->get()
											->row_array();
										$send_email = $cbc_det['user_official_email'];
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 1;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
										$data['ifsc'] = $branch_ifsc;
									} else {
										$send_email = $email;
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 0;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$data['ifsc'] = '';
									}

									$get_mail = $this->customerM->getOTPMail('1');

									$vars = array(
										'[$USER_NAME]'  => ucwords($user_name),
										'[$OTP_CODE]'  	=> ucwords($otp_code)

									);

									$msg 		= strtr($get_mail['body'], $vars);
									$sub 		= $get_mail['subject'];

									$sign =  $msg;
									$sign .= $get_mail['content'];



									$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
									$data['email'] = $user_id;
									$data['email_id'] = $email;
									$data['otp'] = $otp_code;

									if ($chk_email['user_category_id'] != 3) {
										$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
									} else {
										$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
									}

									$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

									$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
									$this->session->set_flashdata('message', $message);
									//redirect('Admin/Welcome/otpCode', $data);

									//$this->load->view('admin/otp_cod',  $data);
									$this->load->view('admin/send_otp',  $data);
								}
							}
						} else {
							$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been terminated.Please contact VIS Admin.</div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin');
						}
					} else {
						$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been suspended.Please contact VIS Admin.</div>';
						$this->session->set_flashdata('message', $message);
						redirect('admin');
					}
				} else {
					$spndData =  $this->customerM->checkSuspendForAdmin($user_id, $chk_email['user_category_id']);
					if ($spndData > 0) {
						$trmData =  $this->customerM->checkTerminationForAdmin($user_id, $chk_email['user_category_id']);
						if ($trmData > 0) {
							$user_id = $chk_email['id'];
							$data['allowBALogin'] = $allowBALogin;
							$data['branch_id'] = $branch_id;
							if ($allowBALogin == 1) {
								$chk_if_BA = $this->customerM->checkIfBA($user_id, $chk_email['user_category_id']);
								if (!empty($chk_if_BA)) {
									$this->form_validation->set_rules('email', 'email', 'trim|required');
									$otp_code = mt_rand(100000, 999999);

									if ($this->form_validation->run() == TRUE) {
										if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
											$cbc_det = $this->db
												->select('*')
												->from('user_details')
												->where(array('user_id' => $user_id))
												->get()
												->row_array();
											$send_email = $cbc_det['user_official_email'];
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 1;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
											$data['ifsc'] = $branch_ifsc;
										} else {
											$send_email = $email;
											$data['send_email'] = $send_email;
											$data['user_type_id'] = 0;
											$data['user_category_id'] = $chk_email['user_category_id'];
											$data['ifsc'] = '';
										}

										$get_mail = $this->customerM->getOTPMail('1');

										$vars = array(
											'[$USER_NAME]'  => ucwords($user_name),
											'[$OTP_CODE]'  	=> ucwords($otp_code)

										);
										$msg 		= strtr($get_mail['body'], $vars);
										$sub 		= $get_mail['subject'];

										$sign =  $msg;
										$sign .= $get_mail['content'];



										$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
										$data['email'] = $user_id;
										$data['email_id'] = $email;
										$data['otp'] = $otp_code;

										if ($chk_email['user_category_id'] != 3) {
											$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
										} else {
											$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
										}

										$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

										$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
										$this->session->set_flashdata('message', $message);
										//redirect('Admin/Welcome/otpCode', $data);

										//$this->load->view('admin/otp_cod',  $data);
										$this->load->view('admin/send_otp',  $data);
									}
								} else {
									$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> You are not authorised for this activity. Kindly contact our Administration at Mob. No/ Email ID.</div>';
									$this->session->set_flashdata('message', $message);
									redirect('admin/welcome/login');
								}
							} else {
								$this->form_validation->set_rules('email', 'email', 'trim|required');
								$otp_code = mt_rand(100000, 999999);

								if ($this->form_validation->run() == TRUE) {
									if ($chk_email['user_type_id'] == 1 && $chk_email['user_category_id'] == 2) {
										$cbc_det = $this->db
											->select('*')
											->from('user_details')
											->where(array('user_id' => $user_id))
											->get()
											->row_array();
										$send_email = $cbc_det['user_official_email'];
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 1;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$branch_ifsc = $this->customerM->getCBCBranchIFSC($user_id);
										$data['ifsc'] = $branch_ifsc;
									} else {
										$send_email = $email;
										$data['send_email'] = $send_email;
										$data['user_type_id'] = 0;
										$data['user_category_id'] = $chk_email['user_category_id'];
										$data['ifsc'] = '';
									}

									$get_mail = $this->customerM->getOTPMail('1');

									$vars = array(
										'[$USER_NAME]'  => ucwords($user_name),
										'[$OTP_CODE]'  	=> ucwords($otp_code)

									);

									$msg 		= strtr($get_mail['body'], $vars);
									$sub 		= $get_mail['subject'];

									$sign =  $msg;
									$sign .= $get_mail['content'];



									$save_opt = array('OTP' => md5($otp_code), 'updated_at' => date('Y-m-d H:i:s'));
									$data['email'] = $user_id;
									$data['email_id'] = $email;
									$data['otp'] = $otp_code;

									if ($chk_email['user_category_id'] != 3) {
										$save_otp = $this->customerM->updateOtp($user_id, $save_opt);
									} else {
										$save_otp = $this->customerM->updateOtpAss($user_id, $save_opt);
									}

									$this->sendEmail($send_email, $sign, $sub, $get_mail['send_to']);

									$message = '<div class="success_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">OTP Code has been send successfully on your email. Please check your email.</div></div>';
									$this->session->set_flashdata('message', $message);
									//redirect('Admin/Welcome/otpCode', $data);

									//$this->load->view('admin/otp_cod',  $data);
									$this->load->view('admin/send_otp',  $data);
								}
							}
						} else {
							$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been terminated.Please contact VIS Admin.</div>';
							$this->session->set_flashdata('message', $message);
							redirect('admin');
						}
					} else {
						$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Your account has been suspended.Please contact VIS Admin.</div>';
						$this->session->set_flashdata('message', $message);
						redirect('admin');
					}
				}
			} else if ($chk_email == 2) {
				if ($allowBALogin == 1) {
					$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Entered Email Id is not registered.</div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome/login');
				} else {
					$message = '<div class="col-sm-12 alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> Entered Email Id is not registered.</div>';
					$this->session->set_flashdata('message', $message);
					redirect('admin/welcome');
				}
			}
		} else {
			redirect('admin/welcome');
		}
	}


	/* create session data */
	public function completeLogin()
	{
		// var_dump($this->input->post());
		//  exit(0);
		$this->is_logged_in();
		if ($this->input->post()) {
			if ($this->input->post('login_type') === 'logint_by_pass') {
				$user_id 	= trim($this->input->post('email_id'));
				$allowBALogin 		= trim($this->input->post('allowBALogin'));
				$branch_id 			= trim($this->input->post('branch_id'));

				$user_category_id 	= trim($this->input->post('user_category_id'));

				$password = $this->input->post('password');

				if ($user_category_id != 3) {

					$login_data = $this->customerM->adminLogin_by_pass($user_id, $password);
					// print_r($login_data);
					// die();
					// 	$login_data = $this->customerM->adminLogin($user_id, $otp);
				} else {
					$login_data = $this->customerM->adminLoginAss_by_pass($user_id, $password);
					// print_r($login_data);
					// die();
					// 	$login_data = $this->customerM->adminLoginAss($user_id, $otp);
				}
			} elseif ($this->input->post('login_type') === 'update_password') {
				// var_dump($this->input->post());

				$user_id 	= trim($this->input->post('email_id'));
				$allowBALogin 		= trim($this->input->post('allowBALogin'));
				$branch_id 			= trim($this->input->post('branch_id'));

				$user_category_id 	= trim($this->input->post('user_category_id'));

				$password = $this->input->post('password');

				if ($user_category_id != 3) {


					$result = $this->db->update("user_details", array('password' => $_POST['password']), array('user_id' => $_POST['email_id']));
					// print_r($result);
					// exit(0);
					$this->session->set_flashdata('success', 'Password Add Succcessfully !');

					redirect(base_url() . 'Admin/Welcome');


					// $login_data = $this->customerM->adminLogin_by_pass($user_id,$password);
					// print_r($login_data);
					// die();
					// 	$login_data = $this->customerM->adminLogin($user_id, $otp);
				} else {
					$login_data = $this->customerM->adminLoginAss_by_pass($user_id, $password);
					// print_r($login_data);
					// die();
					// 	$login_data = $this->customerM->adminLoginAss($user_id, $otp);
				}
			} else {
				$user_id 	= trim($this->input->post('email_id'));
				$otp1 		= trim($this->input->post('otp_1'));
				$otp2 		= trim($this->input->post('otp_2'));
				$otp3 		= trim($this->input->post('otp_3'));
				$otp4 		= trim($this->input->post('otp_4'));
				$otp5 		= trim($this->input->post('otp_5'));
				$otp6 		= trim($this->input->post('otp_6'));

				$allowBALogin 		= trim($this->input->post('allowBALogin'));
				$branch_id 			= trim($this->input->post('branch_id'));

				$user_category_id 	= trim($this->input->post('user_category_id'));

				$otp = $otp1 . $otp2 . $otp3 . $otp4 . $otp5 . $otp6;

				if ($user_category_id != 3) {
					$login_data = $this->customerM->adminLogin($user_id, md5($otp));
					// print_r($login_data);
					// die();
					// 	$login_data = $this->customerM->adminLogin($user_id, $otp);
				} else {
					$login_data = $this->customerM->adminLoginAss($user_id, md5($otp));
					// 	$login_data = $this->customerM->adminLoginAss($user_id, $otp);
				}
			}

			/*by mangal sing yadav
                31-07-2020 */
			$massage = 'Login successfull';
			is_userActivity($user_id, $massage);

			if ($login_data != '404') {
				$get_user_types = $this->customerM->userDetails($user_id);

				$user_category_name = $this->customerM->userCategoryDetails($get_user_types['user_category_id']);

				if ($get_user_types['user_category_id'] === 4) {
					$redirect_url = 'Admin/Welcome/dashboard';
					$user_log_type = 'Admin';
				} else {
					$redirect_url = 'Admin/Welcome/dashboard';
					$user_log_type = 'Other';
				}



				if ($user_category_id != 3) {
					$u_details = $this->db->select('user_details.*, employee_info.resource_image')
						->from('user_details')
						->join('employee_info', 'employee_info.user_id = user_details.user_id')
						->where(array('user_details.status' => 1, 'user_details.user_id' => $get_user_types['id']))
						->get()
						->row_array();
					$image =  $u_details['resource_image'];
					$phone =  $u_details['phone'];
				} else {
					$u_details = $this->db->select('*')
						->from('associate_details')

						->where(array('user_id' => $get_user_types['id']))
						->get()
						->row_array();
					$image =  $u_details['user_image'];
					$phone =  $u_details['personal_mobile_no'];
				}
				$admin_Data = array(
					'userId' 	=> $get_user_types['id'],
					'Name' 		=> ucwords($get_user_types['name']),
					'Email' 	=> $get_user_types['email'],
					'Status' 	=> $get_user_types['status'],
					'image'		=> $image,
					'user_type' => $get_user_types['user_category_id'],
					'user_type_id' => $get_user_types['user_type_id'],
					'phone_no'  => $phone,
					'type_name'	=> $user_category_name['name'],
					'user_login_type' => $user_log_type,
					'user_registration' => $get_user_types['created_at']
				);
				$this->session->set_userdata('adminData', $admin_Data);
				$this->session->set_userdata('allowBALogin', $allowBALogin);
				$this->session->set_userdata('branch_id', $branch_id);
				setcookie("uId", $get_user_types['id'], time() + (86400 * 30), "/");
				$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">Welcome <strong>' . ucwords($get_user_types['name']) . '</strong>.You have successfully logged in.</div></div>';
				$this->session->set_flashdata('message', $message);
				redirect(site_url($redirect_url), 'refresh');
			} else {
				$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Your otp code could not matched. Please try again.</div></div>';
				$this->session->set_flashdata('message', $message);
				redirect(site_url('Admin/Welcome/index'), 'refresh');
			}
		} else {
			$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">Your otp code could not matched. Please try again.</div></div>';

			$this->session->set_flashdata('message', $message);
			redirect(site_url('Admin/Welcome/index'), 'refresh');
		}
	}

	/* call dashboard */
	public function dashboard()
	{
		$this->is_not_logged_in();
		$user_data = $this->session->userdata('adminData');
		$get_data = $this->customerM->getSignupDataCount();
		$user_type     = $this->session->userdata('adminData')['user_type'];
		$userId        = $this->session->userdata('adminData')['userId'];
		$designation_id = $this->session->userdata('designation_id');
		$data['bank_list'] = $this->customerM->getBankList();
		if ($user_type == '4') {
			$BoxData = $this->customerM->getBoxData();
			$get_view_data = $this->w_user->getCaseDataAdmin();
		} else if ($user_type == '1') {
			$BoxData = $this->customerM->getBoxDataByType($userId);
			if ($designation_id == 114) {
				$get_user_department = $this->w_user->getUserDepartment($userId);
				$department = $get_user_department['department'];
				if ($department == 5) {
					$get_view_data = $this->w_user->getLeadForSeniorCoordBA($userId);
				} else {
					$get_view_data = $this->w_user->getLeadForSeniorCoord($userId);
				}
			} else {
				$get_user_department = $this->w_user->getUserDepartment($userId);
				$department = $get_user_department['department'];
				if ($department == 3 || $department == 4 || $department == 5) {
					$get_view_data = $this->w_user->getLeadBA($userId);
				} else {
					$get_view_data = $this->w_user->getLeadCrByEmp($userId);
				}
			}
		} else {
			$BoxData = $this->customerM->getBoxDataByType($userId);
			$get_view_data = $this->w_user->getLeadCrByCust($userId);
		}
		// $BoxData = $this->customerM->getBoxData();
		//print_r($BoxData);
		$data['boxdata'] = $BoxData;
		$get_customer_data = $this->customerM->userTypeCustomer('2');
		$data['customer_data'] = $get_customer_data;
		/* get top 10 customers */
		$cust_list = $this->customerM->getAllCust();
		$data['cust_list'] = $cust_list;
		/* get top 10 leads */
		$this->db->order_by('id', 'desc');
		$this->db->limit(10);
		$total_lead = 0;
		foreach ($get_view_data as $value) {
			$get_app = $this->w_user->getApproval($value['id']);
			if ($get_app == 2) {
				$total_lead++;
			}
		}
		$get_lead_data = $this->w_user->getCaseDataAdmin();
		//print_r(count($get_lead_data));die;
		$i = 0;
		foreach ($get_lead_data as $value) {
			$get_app = $this->w_user->getApproval($value['id']);
			if ($get_app == 2) {
				$i++;
			}
		}
		$get_cases_data = $this->leadM->getCaseLead();
		$j = 0;
		foreach ($get_cases_data as $key1 => $value1) {
			$get_app = $this->w_user->getApproval($value1['id']);
			if ($get_app == 1) {
				$get_case_row = $this->leadM->getCases($value1['id']);
				foreach ($get_case_row as $key2 => $value2) {
					$j++;
				}
			}
		}

		$data['total_leads'] = $total_lead;
		$get_case = $this->leadM->getCaseLeadNum();
		$data['total_cases'] = $j;
		$get_archive = $this->w_user->getArchiveNum();
		$data['total_archive'] = $get_archive;
		if (!empty($set_arr)) {
			$data['res'] = $set_arr; /* get top 10 leads / */
		} else {
			$data['res'] = '';
		}
		$user_name = $this->session->userdata('adminData')['Name'];
		$data['name'] = $user_name;
		$data['data'] = $get_data;
		/***********Get Other Users Menu List***********/
		$menu_arr 		= array();
		$get_menu_data 	= array();
		$user_cat 	= $user_data['user_type'];
		$user_id 	= $user_data['userId'];
		//echo $user_cat;die; 
		if ($user_cat == 1)  // resource
		{
			$emp_data = $this->customerM->userEmployeeDetails($user_id);
			if (!empty($emp_data)) {
				$depart_id 			= $emp_data['department'];
				$job_function_id 	= $emp_data['job_function'];
				$desig_id 			= $emp_data['designation'];
				// echo $depart_id; echo "<br>"; echo $job_function_id; echo "<br>"; echo $desig_id;die;
				$get_previlage = $this->customerM->userPrivilegeDetails($user_cat, $depart_id, $job_function_id, $desig_id);
			}
		} elseif ($user_cat == 2)  // customers
		{
			$desig_id = $user_data['user_type_id'];
			// echo $desig_id;die;
			$get_previlage = $this->customerM->customerPrivilegeDetails($user_cat, $desig_id);
		} elseif ($user_cat == 3)   // associate
		{
			$emp_data = $this->customerM->userAssociateDetails($user_id);
			if (!empty($emp_data)) {
				$depart_id 			= $emp_data['department_id'];
				$job_function_id 	= $emp_data['job_function'];
				$desig_id 			= $emp_data['designation'];
				// echo $depart_id; echo "<br>"; echo $job_function_id; echo "<br>"; echo $desig_id;die;
				$get_previlage = $this->customerM->userPrivilegeDetails($user_cat, $depart_id, $job_function_id, $desig_id);
			}
		} else {
			$desig_id = '';
			$get_previlage = array();
		}
		/******/

		if (!empty($get_previlage)) {
			foreach ($get_previlage as $key => $value) {
				$pre_id =  $value['id'];
				$menu_arr_data = $this->db->select('*')->from('privilege_master_menu_group')->where(array('status' => 1, 'privilege_master_id' => $value['id']))->get()->result_array();
				if (!empty($menu_arr_data)) {
					foreach ($menu_arr_data as $key_m => $value_m) {
						array_push($menu_arr, $value_m['menu_group_id']);
					}
				}
			}

			if (!empty($menu_arr)) {
				foreach ($menu_arr as $p_key => $p_value) {
					$get_menu_group_headings = $this->customerM->getMenuGroupHeadings($p_value);
					foreach ($get_menu_group_headings as $key_heading => $get_menu_group_headings_data) {
						if (!in_array($get_menu_group_headings_data['heading_id'], $get_menu_data)) {
							array_push($get_menu_data, $get_menu_group_headings_data['heading_id']);
						}
					}
				}
			}
		}
		// print_r($get_menu_data);die;
		$this->session->set_userdata('userPermission', $get_menu_data);
		$this->session->set_userdata('designation_id', $desig_id);
		$this->session->set_userdata('userPermittedMenuData', $get_menu_data);

		$allowBALogin           = $this->session->userdata('allowBALogin');
		$branch_id           	= $this->session->userdata('branch_id');

		if ($allowBALogin == '1') {
			if ($branch_id != '0') {
				$redirect_url = 'admin/bankBM/editBankBranchData/' . base64_encode($branch_id);
				redirect(site_url($redirect_url), 'refresh');
			}
		}

		/***************************/
		/*print_r($data);
			die();*/
		$data['title'] = 'VIS | Dashboard';
		// print_r($get_user_type );die;
		$this->load->view('admin/include/header', $data);
		$this->load->view('admin/dashboard');
		$this->load->view('admin/include/footer');
	}


	/* set user dashboard */
	public function setDashboard()
	{
		$this->is_not_logged_in();
		$user_data = $this->session->userdata('adminData');
		// print_r($user_data);die;
		//$user_type 		= $user_data['user_type'];
		$user_id 		= $user_data['userId'];

		// get user category type 
		$get_user_data = $this->db->select('*')
			->from('users')
			->where(array('status' => 1, 'id' => $user_id))
			->get()
			->row_array();
		$this->session->set_userdata('empUserData', $get_user_data);
		$user_cat = $get_user_data['user_type_status'];
		$user_type_id = $get_user_data['user_type_id'];
		$data['user_category_id']  = $user_cat;
		$data['user_type_id']  = '3';
		// echo $user_cat;die;
		$menu_arr = array();
		$get_menu_data = array();

		// print_r($user_type_id);die;
		if ($user_cat == 1) { // internal user 

			$emp_data = $this->db->select('*')
				->from('employee_details')
				->where(array('user_id' => $user_id, 'status' => 1))
				->get()->row_array();
			// print_r($emp_data);die;
			$this->session->set_userdata('empData', $emp_data);

			if (!empty($emp_data)) {
				if ($user_cat == 1) {

					$depart_id = $emp_data['department'];
					$job_function_id = $emp_data['job_function'];
					$desig_id = $emp_data['designation'];

					$get_previlage = $this->db->select('*')
						->from('privilege_master')
						->where(array(
							'user_category_id'		=> $user_cat,
							'department_id'			=> $depart_id,
							'job_function_id'		=> $job_function_id,
							'designation_usertype'	=> $desig_id,
							// 'approving_officer'	=> $user_id,
							'status'			=> 1
						))->get()->result_array();
					// print_r($get_previlage);die;
					$menu_arr_data = '';
					// if($user_type_id == 5) {
					// 	$total_lead = $this->w_user->getLeadBA($user_id);
					// 	$i=0;
					// 	foreach ($total_lead as $value) {
					// 		$get_app = $this->w_user->getApproval($value['id']);
					// 		if($get_app == 2) {
					// 			$i++;
					// 		}
					// 	}
					// 	$total_leads = $i;
					// 	$get_case = $this->leadM->getCaseLeadBANum($user_id);
					// 	$get_archive = $this->w_user->getArchiveBANum($user_id);
					// 	$get_view_data = $this->w_user->getLeadBA($user_id);

					// } else 
					if ($user_cat == 1) {
						$total_leads = 0;
						$get_case = 0;
						$get_archive = 0;
						$get_view_data = '';
					} else if ($user_type_id == 8) {
						$total_leads = 0;
						$get_case = 0;
						$get_archive = 0;
						$get_view_data = '';
					} else if ($user_type_id == 10) {
						$total_leads = 0;
						$get_case = 0;
						$get_archive = 0;
						$get_view_data = '';
					}
				} else {

					$depart_id = $emp_data['department'];

					$desig_id = $emp_data['designation'];
					// echo  $desig_id;die;
					$get_previlage = $this->db->select('*')
						->from('privilege_master')
						->where(array(
							'user_category_id'	=> $user_cat,
							'department_id'		=> $depart_id,
							'designation_usertype'	=> $desig_id,
							// 'approving_officer'	=> $user_id,
							'status'			=> 1
						))->get()->result_array();
					// print_r($get_previlage);die;
					$menu_arr_data = '';
				}
			}
		} else if ($user_cat == 2) {
			$emp_data = $this->db->select('*')
				->from('users')
				->where(array('id' => $user_id, 'status' => 1))
				->get()->row_array();
			// print_r($emp_data);die;
			$this->session->set_userdata('empData', $emp_data);
			$desig_id = $emp_data['user_type_id'];
			$get_previlage = $this->db->select('*')
				->from('privilege_master')
				->where(array(
					'user_category_id'	=> $user_cat,
					// 'department_id'		=> 0,
					'designation_usertype'	=> $desig_id,
					'status'			=> 1
				))->get()->result_array();
			// print_r($get_previlage);die;
			$total_leads = $this->w_user->getLeadCrByCustNum($user_id);
			$get_case = $this->leadM->getCaseLeadByCustNum($user_id);
			$get_archive = $this->w_user->getCaseDataNum($user_id, '15');

			$get_view_data = $this->w_user->getLeadCrByCust($user_id);
		}

		if (!empty($get_previlage)) {
			//print_r($get_previlage);
			foreach ($get_previlage as $key => $value) {
				$pre_id =  $value['id'];
				$menu_arr_data = $this->db->select('*')
					->from('privilege_master_menu_group')
					->where(array('status' => 1, 'privilege_master_id' => $value['id']))
					->get()->result_array();
				if (!empty($menu_arr_data)) {

					foreach ($menu_arr_data as $key_m => $value_m) {
						$menu_arr[$pre_id][] = $value_m['menu_group_id'];
					}
				}
			}
			if (!empty($menu_arr)) {
				foreach ($menu_arr as $p_key => $p_value) {
					$get_menu_data[] = $this->getMenuDatas($p_key, $p_value);
				}
			}
		}
		// print_r($get_menu_data);die;
		// set all data into the session variable 
		$this->session->set_userdata('userPermission', $get_menu_data);
		/*$user_permission = $this->session->userdata('userPermission');
			print_r($user_permission);
			die();*/
		$user_name = $this->session->userdata('adminData')['Name'];
		$data['name'] = $user_name;
		$data['user_id'] = $user_id;
		$data['get_menu_data'] 	= $get_menu_data;
		$data['total_leads']    = $total_leads;
		$data['total_cases'] 	= $get_case;
		$data['total_archive'] 	= $get_archive;
		$data['res']			= $get_view_data;
		$data['title'] = 'VIS | Dashboard';
		$this->load->view('admin/include/common_header', $data);
		$this->load->view('admin/include/common_dashboard', $data);
		$this->load->view('admin/include/footer');
	}

	/* get menu data */
	public function getMenuDatas($p_key, $p_value)
	{
		$save_menu_data = array();
		$save_o_heading_data = array();

		// save all url functions
		$url_arr_1 = array();
		$url_arr_2 = array();
		$url_arr_3 = array();

		if (!empty($p_value)) {
			foreach ($p_value as $key => $value) {

				$i = 0;
				$get_heading = $this->db->select('*')->from('heading_control_system')
					->where(array('status' => 1, 'menu_group_id' => $value))
					->get()->result_array();


				if (!empty($get_heading)) {

					$save_menu_data[$i]['menu_grp_id'] = $value;
					foreach ($get_heading as $key_h_m => $value_h_m) {
						$h_id = $value_h_m['id'];
						$save_menu_data[$i]['headings']['id'] = $value_h_m['id'];
						$save_menu_data[$i]['headings']['heading_name'] = $value_h_m['heading_name'];

						$get_heading_menu = $this->db->select('*')->from('menu_control_system')
							->where(array('status' => 1, 'menu_group_id' => $value))
							->get()->result_array();

						if (!empty($get_heading_menu)) {
							$j = 0;

							foreach ($get_heading_menu as $h_m_key => $h_m_value) {
								if ($h_id == $h_m_value['heading_id']) {
									$save_menu_data[$i]['headings']['menu'][$j]['menu_data'] = $h_m_value;
									$menu_id = $h_m_value['id'];

									$get_sub_menu = $this->db->select('*')->from('submenu_control_system')
										->where(array('status' => 1, 'menu_group_id' => $value, 'parent_menu_id' => $menu_id))
										->get()->result_array();

									if (!empty($get_sub_menu)) {
										$k = 0;

										foreach ($get_sub_menu as $sub_mkey => $sub_mvalue) {

											$save_menu_data[$i]['headings']['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_id'] = $sub_mvalue['id'];
											$save_menu_data[$i]['headings']['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_name'] = $sub_mvalue['sub_menu_name'];
											$save_menu_data[$i]['headings']['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_url'] = $sub_mvalue['sub_menu_url'];
											$k++;
										}
									}
								}

								if (0 == $h_m_value['heading_id']) {
									$save_o_heading_data[$i]['menu'][$j]['menu_data'] = $h_m_value;
									$menu_id = $h_m_value['id'];

									$get_sub_menu = $this->db->select('*')->from('submenu_control_system')
										->where(array('status' => 1, 'menu_group_id' => $value, 'parent_menu_id' => $menu_id))
										->get()->result_array();
									if (!empty($get_sub_menu)) {
										$k = 0;
										foreach ($get_sub_menu as $sub_mkey => $sub_mvalue) {

											$save_o_heading_data[$i]['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_id'] = $sub_mvalue['id'];
											$save_o_heading_data[$i]['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_name'] = $sub_mvalue['sub_menu_name'];
											$save_o_heading_data[$i]['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_url'] = $sub_mvalue['sub_menu_url'];
											$k++;
										}
									}
								}
								$j++;
							}
						}
						$i++;
					}
				} else {
					$get_heading_menu = $this->db->select('*')->from('menu_control_system')
						->where(array('status' => 1, 'menu_group_id' => $value))
						->get()->result_array();

					if (!empty($get_heading_menu)) {
						$j = 0;

						foreach ($get_heading_menu as $h_m_key => $h_m_value) {


							if (0 == $h_m_value['heading_id']) {
								$save_o_heading_data[$i]['menu'][$j]['menu_data'] = $h_m_value;
								$menu_id = $h_m_value['id'];

								$get_sub_menu = $this->db->select('*')->from('submenu_control_system')
									->where(array('status' => 1, 'menu_group_id' => $value, 'parent_menu_id' => $menu_id))
									->get()->result_array();
								if (!empty($get_sub_menu)) {
									$k = 0;
									foreach ($get_sub_menu as $sub_mkey => $sub_mvalue) {

										$save_o_heading_data[$i]['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_id'] = $sub_mvalue['id'];
										$save_o_heading_data[$i]['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_name'] = $sub_mvalue['sub_menu_name'];
										$save_o_heading_data[$i]['menu'][$j]['menu_data']['sub_m'][$k]['sub_menu_url'] = $sub_mvalue['sub_menu_url'];
										$k++;
									}
								}
							}
							$j++;
						}
					}
					$i++;
				}
			}
		}
		$data['without_m'] = $save_o_heading_data;
		$data['menus'] = $save_menu_data;
		return $data;
	}

	/* user session logout */

	public function logout()
	{

		$massage = 'Logout successfull';
		is_userActivity($user_id = '', $massage);

		$this->is_not_logged_in();
		$this->session->sess_destroy();
		setcookie("uId", 0, time() + (86400 * 30), "/");
		$message = '<div class="unsuccess_msg" id="un_secc_msg"><div class="col-xs-12 set_div_msg">You have successfully terminated your session!</div></div>';
		$this->session->set_flashdata('message', $message);
		redirect(site_url('Admin/Welcome/index'), 'refresh');
	} /* logout admin/  */

	/* bank detals */
	public function bankDetails()
	{
		if ($this->input->post()) {
			$bank_id = $this->input->post('id');
			$get_detail = $this->customerM->getBanks($bank_id);
			$branch_id = $get_detail['bank_id'];
			//$add_id = $get_details['pincode_state_city_mapping_id'];

			// $add_details = $this->customerM->getAddDetails($add_id);

			// $c_name = $add_details['city_details']['name'];
			// $s_name = $add_details['state_details']['name'];
			// $pincode = $add_details['add_details']['pincode'];

			$get_details = $this->customerM->getBankBranch($bank_id);

			$mail = explode("@", $get_details['branch_official_email']);
			$set_html = '';
			$set_html .= '<div class="col-sm-12 col-xs-12 divPadding"><div class="col-xs-12 col-sm-12 divPadding set_bottom_p"><div class="col-xs-12 col-sm-12"><b>Name: </b>' . $get_details['bank_name'] . ' (' . $get_details['branch_name'] . ')</div></div><div class="col-xs-12 col-sm-12 divPadding set_bottom_p"><div class="col-xs-12 col-sm-12"><b>Address: </b>' . $get_details['location'] . '</div></div><div class="col-xs-12 col-sm-12 divPadding set_bottom_p"><div class="col-xs-12 col-sm-12"><button type="button" onclick="ViewBranchDetail(' . $bank_id . ')" class="btn btn-sm btn-primary">View More</button></div></div></div><input type="hidden" id="branch_e_id" name="branch_e_id" value="' . $mail['0'] . '@' . $get_details['bank_domain'] . '">';
			echo json_encode(array("set_html" => $set_html, "domain" => $get_details['bank_domain']));
			// $arr =  { "set_html": $set_html, "domain" : $get_details['bank_domain']};
			// print_r($arr);
			die();
		}
	}

	/* check mail before submit data */
	public function checkMailBeforeSubmit()
	{
		if ($this->input->post()) {
			$email = $this->input->post('email');

			$chk_email = $this->customerM->checkEmail($email);
			print_r($chk_email);
			die();
		}
	}

	/* use for send mail when register a user */
	// 		public function sendEmail($email, $sign, $sub, $send_to) {
	// 			$message = '';
	// 			$to      = $email;
	// 			$subject = $sub;
	// 			$message.="&nbsp;".$sign."\r\n";
	// 			$headers = "From:".$send_to."\r\n";
	// 			$headers.= "MIME-Version: 1.0\r\n"; 
	// 			$headers.= "Content-type: text/html; charset=utf-8\r\n";
	// 			mail($to,$subject,'<pre style="font-size:14px;">'.$message.'</pre>',$headers);
	// 			return 1;
	// 		}



	public function sendEmail($email, $sign, $sub, $send_to)
	{
		$file_data = '';
		$this->email->from($send_to, 'VIS');
		$this->email->to($email);
		// $this->email->cc('another@another-example.com');
		// $this->email->bcc('them@their-example.com');
		$this->email->subject($sub);
		$this->email->message($sign);
		$this->email->attach($file_data);
		$this->email->send();
		// 			echo $this->email->print_debugger();
		return 1;
	}





	/* confirm url then activate account */
	public function confirmEmail()
	{
		//$this->is_logged_in();
		$key 		= base64_decode($this->input->get('user_id'));
		$email 		= $this->input->get('email');

		$table 		= 'users';
		$condition 	= array('status' => '4', 'id' => $key);
		$sub 		= "Successfully Registered in VIS";

		if (!empty($key) && !empty($email)) {

			$confirmEmail = $this->customerM->confirmEmail($key, $email, $table, $condition);

			if ($confirmEmail['users']['status'] == '4') {
				$arr_data = $this->customerM->activateUser($key);
				//$confirmUrl = site_url('home/welcome/successConfirmEmail?user_id='.base64_encode($key).'&email='.$email);
				if (!empty($confirmEmail['u_details']['user_official_email'])) {
					$e_id = $confirmEmail['u_details']['user_official_email'];
					$text = '<p style="color:#333;font-size:14px;  margin-top:5px;  margin-bottom:0px;">Credit Team/Branch Email Id: ' . $e_id . '</p>';
				} else {
					$text = '';
				}

				$sign = '<html><body>Hello ' . ucwords($confirmEmail['users']['name']) . '<p style="font-size:14px; margin-top:5px; margin-bottom:0px;">Your Account has been activated with the following information.</p><p style="color:#333;font-size:14px;  margin-top:5px;  margin-bottom:0px;">Detail Informations:</p><p style="color:#333;font-size:14px;  margin-top:5px;  margin-bottom:0px;">Manager Name: ' . ucwords($confirmEmail['users']['name']) . '</p>' . $text . '<p style="color:#333;font-size:14px;  margin-top:5px;  margin-bottom:0px;">Manager Email Id: ' . $confirmEmail['users']['email'] . '</p><p>Thanks,</p><p style=" margin-top:5px; margin-bottom:0px;">Valuation Intelligence System</p></body></html>';

				$this->sendEmail($email, $sign, $sub);
				$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">Your account has been successfully activated. Please login.</div></div>';

				$this->session->set_flashdata('message', $message);
				redirect(site_url('Admin/Welcome/index'), 'refresh');
			} else {
				$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">Your account already activated. Please login.</div></div>';

				$this->session->set_flashdata('message', $message);
				redirect(site_url('Admin/Welcome/index'), 'refresh');
			}
		}
	} /* confirm url then activate account / */

	/* view faqs page */
	public function faqs()
	{
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$get_faqs = $this->customerM->getFaqs();
		$data['res'] = $get_faqs;
		$data['title'] = 'FAQs';

		//$this->load->view('admin/faq', $data);
		$this->load->view('admin/new_faqs', $data);
	} /* view faqs page / */

	/* view terms page */
	public function terms()
	{
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$data['title'] = 'Terms';
		$data['title'] = 'VIS | Terms & Conditions';
		$data['get_data'] = $this->db->select('*')
			->from('pages_master')
			->where(array(
				'status'	=> 1,
				'page_id'	=> 2,
			))
			->get()->row_array();

		//$this->load->view('admin/terms');
		$this->load->view('admin/new_terms', $data);
	} /* view terms page / */

	/* view terms page */
	public function success()
	{
		$page_id = $this->uri->segment(4, 0);
		$user_id = $this->uri->segment(5, 0);
		$data['get_data'] = $this->db->select('*')
			->from('users')
			->where(array(
				'id'	=> $user_id
			))
			->get()->row_array();
		$data['page_id'] = $page_id;
		$data['title'] = 'VIS | Success Page';
		$this->load->view('admin/succsess', $data);
	} /* view terms page / */

	/* view terms page */
	public function updateSuccess()
	{
		$old_branch = $this->uri->segment(4, 0);
		$new_branch = $this->uri->segment(5, 0);

		$old_branch_det = $this->db->select('*')
			->from('bank_branch_master')
			->where(array(
				'id'	=> $old_branch,
			))
			->get()->row_array();

		$data['old_branch_name'] = $old_branch_det['branch_name'];

		$new_branch_det = $this->db->select('*')
			->from('bank_branch_master')
			->where(array(
				'id'	=> $new_branch,
			))
			->get()->row_array();

		$data['new_branch_name'] = $new_branch_det['branch_name'];

		$data['title'] = 'VIS | Success Page';
		$this->load->view('admin/success', $data);
	} /* view terms page / */

	/* view terms page */
	public function privacyPolicy()
	{
		$data['title'] = 'VIS | Privacy & Policy Page';
		$this->load->view('admin/privacy_policy');
	} /* view terms page / */

	/* view terms page */
	public function cookiePolicy()
	{
		$data['title'] = 'VIS | Cookie Policy Page';
		$this->load->view('admin/cookie_policy');
	} /* view terms page / */

	/* view terms page */
	public function contactUs()
	{
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		if ($this->input->post()) {

			$name = trim($this->input->post('name'));
			$email = trim($this->input->post('email'));
			$mobile = trim($this->input->post('mobile'));
			$inquery_type = trim($this->input->post('inquery_type'));
			$comment = trim($this->input->post('comment'));

			$dates = date('Y-m-d H:i:s');
			$set_data = array(
				'name'			=> $name,
				'email'			=> $email,
				'contact_no	'	=> $mobile,
				'inquery_type'	=> $inquery_type,
				'comment'		=> $comment,
				'status'		=> 1,
				'created_at'	=> $dates,
				'updated_at'	=> $dates,
			);
			$insert_data = $this->customerM->saveContacts($set_data);
			$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">Your enquiry form has been successfully submited. <span class="cross"><i class="fa fa-times" aria-hidden="true"></i></span></div></div>';
			$this->session->set_flashdata('message', $message);
			redirect(site_url('admin/welcome/contactUs'), 'refresh');
		}

		$data['add_data'] = $this->db->select('*')
			->from('pages_master')
			->where(array(
				'status'	=> 1,
				'page_id'	=> 3,
			))
			->get()->result_array();

		$get_data = $this->customerM->getInqueryData();
		$data['title'] = 'Contact Us';
		$data['get_data'] = $get_data;
		//$this->load->view('admin/contactus', $data);
		$this->load->view('admin/new_contactus', $data);
	} /* view terms page / */

	/* set input values */
	public function setInputs()
	{
		if ($this->input->post()) {
			$str = $this->input->post('in_values');
			//$ex_val = substr($str, 0, 1);
			$chars = str_split($str, 1);
			//print_r($chars);
			$set_in = '';
			$i = 1;
			foreach ($chars as $key => $value) {
				$set_in .= '<input type="text" class="set_input_otp set_m" name="otp_' . $i . '" placeholder="" id="otp_' . $i . '" data-id="' . $i . '" maxlength="1" value="' . $value . '">';
				$i++;
			}
			echo json_encode($set_in);
			die();
		}
	}

	/* set input values */
	public function setInputs1()
	{
		if ($this->input->post()) {
			$str = $this->input->post('in_values');
			//$ex_val = substr($str, 0, 1);
			$chars = str_split($str, 1);
			//print_r($chars);
			$set_in = '';
			$i = 1;
			foreach ($chars as $key => $value) {
				$set_in .= '<input type="text" class="set_input_otp set_m" name="otp_' . $i . '" placeholder="" id="otp1_' . $i . '" data-id="' . $i . '" maxlength="1" value="' . $value . '" style="width: 12.6% !important;">';
				$i++;
			}
			echo json_encode($set_in);
			die();
		}
	}

	/* static pages about us */
	public function aboutUs()
	{
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$data['title'] = 'VIS | About Us';
		$data['get_data'] = $this->db->select('*')
			->from('pages_master')
			->where(array(
				'status'	=> 1,
				'page_id'	=> 1,
			))
			->get()->row_array();

		$this->load->view('admin/about_us', $data);
	}

	public function getBranchDetails()
	{
		if ($this->input->post()) {
			$branch_id = $this->input->post('branch_id');
			$result = $this->db->select('*')
				->from('bank_branch_master')
				->where(array(
					'id'	=> $branch_id,
				))
				->get()->row_array();
			$table = 'bank_details';
			$condi = array('id' => $result['bank_id']);
			$bank_data = $this->customerM->getdatas($table, $condi, $result['bank_id']);

			$branch_category_data = $this->customerM->getdatas('bank_branch_category', array('id' => $result['branch_category_id']), $result['branch_category_id']);

			$explode_branch_off_email = explode("@", $result['branch_official_email']);
			$branch_off_email = $explode_branch_off_email[0] . "@" . $bank_data['bank_domain'];
			$branch_head_category = '';
			$branch_head_category_data = $this->customerM->getdatas('bank_branch_head_category', array('id' => $result['branch_head_category_id']), $result['branch_head_category_id']);
			if (!empty($branch_head_category_data)) {
				$branch_head_category = $branch_head_category_data['branch_head_category_name'];
			}

			$explode_branch_head_email = explode("@", $result['branch_head_email']);
			$branch_head_email = $explode_branch_head_email[0] . "@" . $bank_data['bank_domain'];

			$state_data = $this->customerM->getdatas('master_states', array('id' => $result['state']), $result['state']);
			$district_data = $this->customerM->getdatas('master_district', array('id' => $result['city']), $result['city']);

			$html = '<div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Name <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['branch_name'] . '" placeholder="Branch Name" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Category <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $branch_category_data['branch_category_name'] . '" placeholder="Branch Category" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">IFSC Code <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['ifsc_code'] . '" placeholder="IFSC Code" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Code <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['branch_code'] . '" placeholder="Branch Code" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Official Email Id <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $branch_off_email . '" placeholder="Branch Official Email Id" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Head Category <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $branch_head_category . '" placeholder="Branch Head Category" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Head Email Id <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $branch_head_email . '" placeholder="Branch Head Email Id" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Address <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['location'] . '" placeholder="Address" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">State <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $state_data['name'] . '" placeholder="State" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">District <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $district_data['name'] . '" placeholder="District" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Pincode <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['pincode'] . '" placeholder="Pincode" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">STD Code <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['std_code'] . '" placeholder="STD Code" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 divPadding">
							<div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Phone Number <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['branch_phone_no'] . '" placeholder="Branch Phone Number" readonly>
                                </div>
                            </div>
                            <div class="form-group col-sm-6 col-xs-12 manage_divP">
                                <div  class="col-xs-4">
                                    <label for="exampleInputEmail1">Branch Mobile Number <span class="red">*</span></label>
                                </div>
                                <div  class="col-xs-8">
                                    <input type="text" class="form-control" name="branch_name" id="branch_name" value="' . $result['branch_mobile_no'] . '" placeholder="Branch Mobile Number" readonly>
                                </div>
                            </div>
                        </div>';

			$url = base_url() . 'admin/welcome/login/' . $branch_id;

			$result = array("html" => $html, "url" => $url);
			echo json_encode($result);
		}
	}


	public function updateAccept()
	{
		if ($this->input->post()) {
			$id = $this->input->post('id');
			$update = $this->customerM->updateAccept($id);
			$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… Please check your Credit Team/Branch Email Id to activate your account.</div></div>';
			$this->session->set_flashdata('message', $message);
			redirect('admin/welcome/success');
		}
	}

	public function getStates()
	{
		if ($this->input->post()) {
			$id = $this->input->post('con_id');
			$states = $this->customerM->getState($id);
			$con = '';
			$con .= '<option value="0">Select States</option>';
			foreach ($states as $key => $value) {
				$con .= "<option value='$value[id]'>$value[name]</option>";
			}

			echo $con;
		}
	}


	public function getCities()
	{
		if ($this->input->post()) {
			$id = $this->input->post('s_id');
			$states = $this->customerM->getCities($id);
			$con = '';
			$con .= '<option value="0">Select District</option>';
			foreach ($states as $key => $value) {
				$con .= "<option value='$value[id]'>$value[name]</option>";
			}
			echo $con;
		}
	}


	public function getCorpAddress()
	{
		if ($this->input->post()) {
			$con 		= $this->input->post('con');
			$stateid 	= $this->input->post('state');
			$cityid 	= $this->input->post('city');
			$address 	= $this->input->post('address');
			$pincode 	= $this->input->post('pincode');
			$countries  = $this->customerM->get_Countries();
			$state 		= $this->customerM->getState($con);
			$city 		= $this->customerM->getCities($stateid);
			$res = '<div id="hide4" class="col-sm-12 col-xs-12 divPadding"><div class="input-field col-sm-6"><div class="form-group"><label>Country:</label><select class="form-control" id="country_com" name="country_com" disabled>';

			foreach ($countries as $key => $value) {
				if ($value['id'] == $con) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$res .= '<option value="' . $value['id'] . '" ' . $sel . '>' . $value['name'] . '</option>';
			}
			$res .= '</select><p id="num_country_com" class="" style="display: none;"></p></div></div><div class="input-field col-sm-6"><div class="form-group"><label>Address:</label><input type="text" name="address_com" class="form-control" placeholder="Address" id="address_com" value="' . $address . '" disabled><p id="num_err_cug" class="cug" style="display: none;"></p></div></div</div></div></div><div id="hide4" class="col-sm-12 col-xs-12 divPadding"><div class="input-field col-sm-6"><div class="form-group"><label>State:</label><select class="form-control" id="state_com" name="state_com" disabled><option value="0">Select State</option>';

			foreach ($state as $key1 => $value1) {
				if ($value1['id'] == $stateid) {
					$sel1 = "selected";
				} else {
					$sel1 = "";
				}
				$res .= '<option value="' . $value1['id'] . '" ' . $sel1 . '>' . $value1['name'] . '</option>';
			}

			$res .= '</select><p id="num_err_personal" class="personal" style="display: none;"></p></div></div><div class="input-field col-sm-6"><div class="form-group"><label>City:</label><select class="form-control" id="city_com" name="city_com" disabled><option value="0">Select City</option>';

			foreach ($city as $key2 => $value2) {
				if ($value2['id'] == $cityid) {
					$sel2 = "selected";
				} else {
					$sel2 = "";
				}
				$res .= '<option value="' . $value2['id'] . '" ' . $sel2 . '>' . $value2['name'] . '</option>';
			}

			$res .= '</select><p id="num_err_cug" class="cug" style="display: none;"></p></div></div></div><div id="hide4" class="col-sm-12 col-xs-12 divPadding"><div class="input-field col-sm-6"><div class="form-group"><label>Pin Code:</label><input type="text" name="pincode_com" class="form-control set_input_num" placeholder="Personal Mobile No." id="personal" maxlength="10" value="' . $pincode . '" disabled><p id="num_err_personal" class="personal" style="display: none;"></p></div></div></div></div>';



			echo $res;
		}
	}



	/* show all customers */
	public function showCustomer()
	{
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$data['query'] = $this->db
			->select('customer_dashboard.id as c_id, customer_dashboard.image,  customer_dashboard.title, customer_dashboard.discription, customer_dashboard.read_more_link, customer_dashboard.set_up_link, customer_usertype.name')
			->from('customer_dashboard')
			->join('customer_usertype', 'customer_usertype.id = customer_dashboard.title')
			->where('customer_dashboard.status', 1)
			->get()
			->result_array();

		$this->load->view('admin/subscribe_customer', $data);
	}

	/* show bank customer forms */
	public function customers()
	{
		$data['state_pwd_data'] 	= $this->get_pwd_state_data();
		$page_id = $this->uri->segment(4, 0);
		$get_user_type = $this->customerM->selectData($page_id);
		$data['user_type'] = $get_user_type;
		$sub = "Account verification notification";
		$get_countries = $this->customerM->get_Countries();
		$data['get_countries'] = $get_countries;

		$data['get_ifsc'] = $this->customerM->getIFSCCode();

		if ($page_id == 1) {
			$this->load->view('admin/customer_pages/banks_customers', $data);
		} else if ($page_id == 2) {
			$this->load->view('admin/customer_pages/nbfc_customers', $data);
		} else if ($page_id == 3) {
			$this->load->view('admin/customer_pages/carporate_customers', $data);
		} else if ($page_id == 4) {
			$this->load->view('admin/customer_pages/individual_customer', $data);
		} else if ($page_id == 5) {
			$this->load->view('admin/customer_pages/irp_customer', $data);
		} else if ($page_id == 6) {
			$this->load->view('admin/customer_pages/arc_customers', $data);
		}
	}


	public function getCustList()
	{
		if ($this->input->post()) {
			$id 		   = $this->input->post('id');
			$get_cust_list = $this->customerM->userCustList($id);
			$res = "<option value='0'>Select Customer</option>";
			foreach ($get_cust_list as $key => $value) {
				$res .=  "<option value='$value[id]'>$value[name]</option>";
			}
			$get_lead_data = $this->w_user->getLeadNumByUserType($id);
			$i = 0;
			foreach ($get_lead_data as $value) {
				$get_app = $this->w_user->getApproval($value['id']);
				if ($get_app == 2) {
					$i++;
				}
			}
			$total_lead  = $i;
			$get_case 	 = $this->leadM->getCaseNumByUserType($id);
			$get_archive = $this->w_user->getArchiveNumByUserType($id);
			$get_view_data = $this->w_user->getTopLead($id);
			// $i=1;
			for ($i = 1; $i < 7; $i++) {
				$m = date('m', strtotime("-$i month"));
				$y = date('Y', strtotime("-$i month"));
				$lastmonth_lead = $this->w_user->getLastMonthLeadCustType($id, $m, $y);
				$l_lead[$i] =  $lastmonth_lead;
				$lastmonth_case = $this->w_user->getLastMonthCaseCustType($id, $m, $y);
				$l_case[$i] =  $lastmonth_case;
				$lastmonth_arch = $this->w_user->getLastMonthArchCustType($id, $m, $y);
				$l_archive[$i] =  $lastmonth_arch;
			}
			$toplead = "";
			$i = 1;
			foreach ($get_view_data as $key => $value) {
				$get_app = $this->w_user->getApproval($value['id']);
				$get_sur_data = $this->w_user->assignLead($value['id']);
				$get_cr_data = $this->w_user->cr_data($value['created_by']);
				$name = ucwords($get_cr_data['name']);
				$get_status_data = $this->w_user->enqueryStatus($value['status']);
				if (!empty($get_sur_data)) {
					$sur_id = $get_sur_data['id'];
					$sur_name = $get_sur_data['name'];
				} else {
					$sur_name = 'N/A';
					$sur_id = '';
				}
				switch ($value['status']) {
					case '1':
						$status_button = '<div style="" class="label label-info lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					case '2':
						$status_button = '<div style="background: #9370DB;" class="label lbl label-primary status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					case '3':
						$status_button = '<div style="" class="label label-success lbl  status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					case '4':
						$status_button = '<div style="background: #00FA9A;" class="label lbl label-success status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					default:
						$status_button = '<div style="" class="label lbl label-danger status_button">Cancel Enquiry</div>';
						break;
				}
				if ($value['share_link_or_not'] == 1) {
					$bk_clr = 'set_bg';
				} else {
					$bk_clr = '';
				}
				$date_f = strtotime($value['created_at']);
				$date_formate = date('d-m-Y H:i', $date_f);
				$edit_case_url      = 'admin/webU/editCase';
				$url = base_url($edit_case_url) . '/' . base64_encode($value['id']);
				$edit = 'edit_' . $value['id'];
				if ($get_app == 2) {
					$toplead .= "<tr id='$value[id]' class='main_row $bk_clr'><td>$i</td><td>$value[lead_ids]</td><td>$value[customer_name]</td><td>$value[co_persone_name]</td><td>$value[co_persone_email]</td><td>$value[co_persone_number]</td><td>$sur_name</td><td>$name</td><td id='act' width='200' style='padding:10px;'>$status_button</td><td width='100'>$date_formate</td><td><a title='Edit Case' href='$url' id='$edit'><button type='button'><i class='fa fa-edit'></i></button></a><a title='Cancel Lead' href='javascript: void(0);' id='can_$value[id]' data-id='$value[id]' class='cancel_lead'><button type='button'><i class='fa fa-ban' aria-hidden='true'></i></button></a></td></tr>";
				}
				$i++;
			}
			$result = array("res" => $res, "total_lead" => $total_lead, "total_case" => $get_case, "total_archive" => $get_archive, "toplead" => $toplead, "l_lead" => $l_lead, "l_case" => $l_case, "l_archive" => $l_archive);
			echo json_encode($result);
		}
	}


	public function getCustListData()
	{
		if ($this->input->post()) {
			$id 		   = $this->input->post('id');
			$total_leads = $this->w_user->getLeadCrByCustNum($id);
			$get_case = $this->leadM->getCaseLeadByCustNum($id);
			$get_archive = $this->w_user->getCaseDataNum($id, '15');
			$get_view_data = $this->w_user->getLeadCrByCust($id);

			$toplead = '';
			$i = 1;
			foreach ($get_view_data as $key => $value) {
				$get_app = $this->w_user->getApproval($value['id']);
				$get_sur_data = $this->w_user->assignLead($value['id']);
				$get_cr_data = $this->w_user->cr_data($value['created_by']);
				$name = ucwords($get_cr_data['name']);
				$get_status_data = $this->w_user->enqueryStatus($value['status']);
				if (!empty($get_sur_data)) {
					$sur_id = $get_sur_data['id'];
					$sur_name = $get_sur_data['name'];
				} else {
					$sur_name = 'N/A';
					$sur_id = '';
				}
				switch ($value['status']) {
					case '1':
						$status_button = '<div style="" class="label label-info lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					case '2':
						$status_button = '<div style="background: #9370DB;" class="label lbl label-primary status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					case '3':
						$status_button = '<div style="" class="label label-success lbl  status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					case '4':
						$status_button = '<div style="background: #00FA9A;" class="label lbl label-success status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;

					default:
						$status_button = '<div style="" class="label lbl label-danger status_button">Cancel Enquiry</div>';
						break;
				}
				if ($value['share_link_or_not'] == 1) {
					$bk_clr = 'set_bg';
				} else {
					$bk_clr = '';
				}
				$date_f = strtotime($value['created_at']);
				$date_formate = date('d-m-Y H:i', $date_f);
				$edit_case_url      = 'admin/webU/editCase';
				$url = base_url($edit_case_url) . '/' . base64_encode($value['id']);
				$edit = 'edit_' . $value['id'];
				if ($get_app == 2) {
					$toplead .= "<tr id='$value[id]' class='main_row $bk_clr'><td>$i</td><td>$value[lead_ids]</td><td>$value[customer_name]</td><td>$value[co_persone_name]</td><td>$value[co_persone_email]</td><td>$value[co_persone_number]</td><td>$sur_name</td><td>$name</td><td id='act' width='200' style='padding:10px;'>$status_button</td><td width='100'>$date_formate</td><td><a title='Edit Case' href='$url' id='$edit'><button type='button'><i class='fa fa-edit'></i></button></a><a title='Cancel Lead' href='javascript: void(0);' id='can_$value[id]' data-id='$value[id]' class='cancel_lead'><button type='button'><i class='fa fa-ban' aria-hidden='true'></i></button></a></td></tr>";
				}
				$i++;
			}
			$result = array("total_lead" => $total_leads, "total_case" => $get_case, "total_archive" => $get_archive, "toplead" => $toplead);
			echo json_encode($result);
		}
	}

	public function getData()
	{
		$val		   = $this->input->post('val');
		$user_type     = $this->session->userdata('adminData')['user_type'];
		$userId        = $this->session->userdata('adminData')['userId'];
		if ($user_type == '4') {
			$boxdata = $this->customerM->getBoxData();
		} else {
			$boxdata = $this->customerM->getBoxDataByType($userId);
		}



		$html = '';
		$html1 = '';
		if ($val == 1) {
			$html .= '<div class="col-md-6"><div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Customers Data</h3><div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button><button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button></div></div>
                    <div class="box-body chart" style="padding: 0 0 19px 0;"><div class="m-widget1"><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Commercial Banks Customers</h4><span class="m-widget1__desc">Total Commercial Banks Customers</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-brand" id="cbc">' . $boxdata['res_cbc'] . '</span></div></div></div><div class="m-widget1__item" ><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">NBFC Customers</h4><span class="m-widget1__desc">Total NBFC Customers</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-purple" id="nbfc">' . $boxdata['res_nbfc'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Coorporate Customers</h4><span class="m-widget1__desc">Total Coorporate Customers</span></div>
                    <div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-success" id="cor">' . $boxdata['res_cor'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Individual Private Customers</h4>
                        <span class="m-widget1__desc">Total Individual Private Customers</span></div>
                    <div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning" id="ipc">' . $boxdata['res_ipc'] . '</span></div></div></div>
                    <div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">IRP Customers</h4><span class="m-widget1__desc">Total IRP Customers</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-red" id="irp">' . $boxdata['res_irp'] . '</span></div></div></div>
                    <div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">ARC Customers</h4><span class="m-widget1__desc">Total ARC Customers</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-pink" id="arc">' . $boxdata['res_arc'] . '</span></div></div></div>
                    <div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Active Customers</h4><span class="m-widget1__desc">Total Active Customers</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-blue" id="act_cus">' . $boxdata['active_cust'] . '</span></div></div></div></div></div></div></div>';

			$cust_list = $this->customerM->getAllCust();
			$html1 .= '<thead><tr><td><b>S. No.</b></td><td><b>Customer Type</b></td><td><b>Manager Name</b></td><td><b>Email id</b></td><td width="200"><b>Status</b></td><td width="100"><b>Created Date</b></td></tr></thead><tbody>';
			$i = 1;
			foreach ($cust_list as $value) {
				$d1 = strtotime($value['created_at']);
				$sch_date11 = date('d-m-Y', $d1);
				if ($value['user_type_id'] == '1') {
					$n = "Commercial Banks Customers";
				} else if ($value['user_type_id'] == '2') {
					$n = "NBFC Customers";
				} else if ($value['user_type_id'] == '3') {
					$n = "Coorporate Customers";
				} else if ($value['user_type_id'] == '4') {
					$n =  "Individual Private Customers";
				} else if ($value['user_type_id'] == '5') {
					$n =  "IRP Customers";
				} else if ($value['user_type_id'] == '6') {
					$n =  "ARC";
				}


				$html1 .= '<tr id="' . $value['id'] . '" class="main_row"><td>' . $i . '</td><td>' . $n . '</td><td>' . $value['name'] . '</td><td>' . $value['email'] . '</td><td id="act" style="padding: 10px;">';
				if ($value['agree_terms_cond'] == 1) {
					$html1 .= '<div class="label label-success lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Active</div>';
				} else {
					$html1 .= '<div class="label label-warning lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Deactive</div>';
				}

				$html1 .= '</td><td>' . $sch_date11 . '</td></tr>';
				$i++;
			}
			$html1 .= '<tbody>';
		} else if ($val == 2) {

			if ($user_type == '4') {
				$emp_list = $this->db->query("SELECT * from users where status = 3 and resource_status = 1 or resource_status = 2 ORDER by id DESC limit 10")->result_array();
			} else {
				$emp_list = $this->db->select('*')
					->from('users')
					->where(array('status' => 1, 'resource_status' => 2, 'created_by' => $userId))
					->limit(10)
					->order_by('id', 'desc')
					->get()
					->result_array();
			}


			$html .= '<div class="col-md-6"><div class="box box-purple"><div class="box-header with-border"><h3 class="box-title">Employees Data</h3><div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button><button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button></div>
                    </div><div class="box-body chart" style="padding: 0 0 19px 0;"><div class="m-widget1"><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending For Resource Form</h4>
                        <span class="m-widget1__desc">Total Resources Pending For Resource Form</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-blue" id="res_pen_form">' . $boxdata['res_pen_form'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending For HR Review</h4><span class="m-widget1__desc">Total Resources Pending For HR Review</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning2" id="pen_for_hr">' . $boxdata['res_pen_rev'] . '</span></div></div></div><div class="m-widget1__item" ><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending For Manager Approval</h4><span class="m-widget1__desc">Total Resources Pending For Manager Approval</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning" id="pen_for_app">' . $boxdata['res_pen_app'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Total Resources</h4><span class="m-widget1__desc">Total Resources</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-purple" id="res">' . $boxdata['total_res'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Total Employees</h4><span class="m-widget1__desc">Total Employees</span></div>
                    <div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-brand" id="emp">' . $boxdata['total_emp'] . '</span></div></div></div></div></div></div></div>';


			$html1 .= '<thead><tr><td><b>S. No.</b></td><td><b>RES ID/EMP ID</b></td><td><b>Name</b></td><td><b>Email id</b></td><td><b>Mobile No.</b></td><td width="200"><b>Status</b></td></tr></thead><tbody';
			$i = 1;
			foreach ($emp_list as $value) {
				$emp_data = $this->hrM->getEmp($value['id']);


				$html1 .= '<tr id="' . $value['id'] . '" class="main_row"><td>' . $i . '</td><td>' . $value['employee_id'] . '</td><td>' . $value['name'] . '</td><td>' . $value['email'] . '</td><td>' . $emp_data['cus_mobile_no'] . '</td><td id="act" style="padding: 10px;">';
				if ($value['resource_status'] == 1) {
					if ($value['status'] == 2) {
						$html1 .= '<div class="label label-success lbl" id="ids_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Pending for Manager Approval</div>';
					} else if ($value['status'] == 1) {
						$html1 .= '<div class="label label-warning lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Pending for Resource Form</div>';
					} else if ($value['status'] == 3) {
						$html1 .= '<div class="label label-info lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Pending for HR Review</div>';
					} else if ($value['status'] == 4) {
						$html1 .= '<div class="label label-warning lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Pending</div>';
					} else if ($value['status'] == 5) {
						$html1 .= '<div class="label label-info lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Decline</div>';
					} else if ($value['status'] == 6) {
						$html1 .= '<div class="label label-danger lbl" id="id_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Cancelled</div>';
					}
				} else {
					$html1 .= '<div class="label label-success lbl" id="ids_' . $value['id'] . '" s_id="' . $value['id'] . '" type="button">Active Employee</div>';
				}

				$html1 .= '</td></tr>';
				$i++;
			}

			$html1 .= '</tbody>';
		} else if ($val == 3) {
			if ($user_type == '4') {
				$ass_list     = $this->hrM->assDataByAdminLimit();
			} else {
				$ass_list = $this->db->select('*')
					->from('associate_details')
					->where(array('status' => 1, 'added_by_id' => $userId))
					->limit(10)
					->order_by('id', 'desc')
					->get()
					->result_array();
			}
			$html .= '<div class="col-md-6"><div class="box box-success"><div class="box-header with-border">
                      <h3 class="box-title">Associates Data</h3><div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button><button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button></div>
                    </div><div class="box-body chart" style="padding: 0 0 19px 0;"><div class="m-widget1"><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending For Associate Form</h4><span class="m-widget1__desc">Total Associates Pending For Associate Form</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning2" id="pen_ass">' . $boxdata['pen_ass'] . '</span></div></div></div><div class="m-widget1__item" ><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending For HR Review</h4><span class="m-widget1__desc">Total Associates Pending For HR Review</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning" id="pen_ass_rev">' . $boxdata['pen_ass_rev'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Active Associates</h4><span class="m-widget1__desc">Total Active Associates</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-success" id="act_ass">' . $boxdata['app_ass'] . '</span></div></div></div></div></div></div></div>';


			$html1 .= '<thead><tr><td><b>S. No.</b></td><td><b>Associate ID</b></td><td><b>Associate Name</b></td><td><b>Personal Email id</b></td><td><b>Mobile No.</b></td><td width="200"><b>Status</b></td></tr></thead><tbody>';
			$i = 1;
			foreach ($ass_list as $value) {
				$getUsers = $this->hrM->getDataById('users', $value['user_id']);
				$permission_active = '2';

				$html1 .= '<tr id="' . $value['id'] . '" class="main_row"><td>' . $i . '</td><td>' . $getUsers['employee_id'] . '</td><td>' . $getUsers['name'] . '</td><td>' . $getUsers['email'] . '</td><td>' . $value['personal_mobile_no'] . '</td><td id="act" style="padding: 10px;">';

				if ($getUsers['employee_status'] == 1 || $getUsers['employee_status'] == 0) {
					$html1 .= '<div class="label label-warning lbl" id="' . $getUsers['id'] . '" ' . $getUsers['id'] . '" type="button">Pending for Associate Form</div>';
				} else if ($getUsers['employee_status'] == 2) {
					$html1 .= '<div class="label label-success lbl" id="empid_' . $getUsers['id'] . '" s_id="' . $getUsers['id'] . '" type="button">Approved</div>';
				} else if ($getUsers['employee_status'] == 3) {
					$html1 .= '<div class="label label-info lbl" id="empid_' . $getUsers['id'] . '" s_id="' . $getUsers['id'] . '" type="button">Pending for HR Review</div>';
				}

				$html1 .= '</td></tr>';
				$i++;
			}

			$html1 .= '</tbody>';
		} else if ($val == 4) {
			$html .= '<div class="col-md-6"><div class="box box-warning"><div class="box-header with-border">
                      <h3 class="box-title">VIS Cases Data</h3><div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button><button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button></div>
                    </div><div class="box-body chart" style="padding: 0 0 19px 0;"><div class="m-widget1"><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for Mandatory Documentations</h4><span class="m-widget1__desc">Total Pending for Mandatory Documentations</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-brand" id="sur_in_progress">' . $boxdata['c_status_6'] . '</span></div></div></div><div class="m-widget1__item" ><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for Surveyor Assignment</h4><span class="m-widget1__desc">Total Pending for Surveyor Assignment</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning2" id="rep_prep">' . $boxdata['c_status_7'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Survey in Progress</h4><span class="m-widget1__desc">Total Survey in Progress</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-purple" id="res_l1_rev">' . $boxdata['c_status_8'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for Preparer Assignment</h4><span class="m-widget1__desc">Total Pending for Preparer Assignment</span></div>
                    <div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning" id="rep_under_app">' . $boxdata['c_status_9'] . '</span></div></div></div>

	                    <div class="m-widget1__item">
	                    	<div class="row m-row--no-padding align-items-center">
	                    		<div class="col-sm-9">
	                    			<h4 class="m-widget1__title">Report preparation in Progress</h4>
	                    			<span class="m-widget1__desc">Total Report preparation in Progress</span>
	                			</div>
	                    		<div class="col-sm-3" style="margin-top: 1.0em;">
	                    			<span class="m-widget1__number m--font-blue" id="res_disp">' . $boxdata['c_status_10'] . '</span>
	                			</div>
	                		</div>
	                	</div>
	                	<div class="m-widget1__item">
	                    	<div class="row m-row--no-padding align-items-center">
	                    		<div class="col-sm-9">
	                    			<h4 class="m-widget1__title">Pending for L1 review</h4>
	                    			<span class="m-widget1__desc">Total Pending for L1 review</span>
	                			</div>
	                    		<div class="col-sm-3" style="margin-top: 1.0em;">
	                    			<span class="m-widget1__number m--font-blue" id="status_11">' . $boxdata['c_status_11'] . '</span>
	                			</div>
	                		</div>
	                	</div>
	                	<div class="m-widget1__item">
	                    	<div class="row m-row--no-padding align-items-center">
	                    		<div class="col-sm-9">
	                    			<h4 class="m-widget1__title">Pending for L2 review</h4>
	                    			<span class="m-widget1__desc">Total Pending for L2 review</span>
	                			</div>
	                    		<div class="col-sm-3" style="margin-top: 1.0em;">
	                    			<span class="m-widget1__number m--font-blue" id="status_12">' . $boxdata['c_status_12'] . '</span>
	                			</div>
	                		</div>
	                	</div>
	                	<div class="m-widget1__item">
	                    	<div class="row m-row--no-padding align-items-center">
	                    		<div class="col-sm-9">
	                    			<h4 class="m-widget1__title">Pending for Client Approval</h4>
	                    			<span class="m-widget1__desc">Total Pending for Client Approval</span>
	                			</div>
	                    		<div class="col-sm-3" style="margin-top: 1.0em;">
	                    			<span class="m-widget1__number m--font-blue" id="status_13">' . $boxdata['c_status_13'] . '</span>
	                			</div>
	                		</div>
	                	</div>
	                	<div class="m-widget1__item">
	                    	<div class="row m-row--no-padding align-items-center">
	                    		<div class="col-sm-9">
	                    			<h4 class="m-widget1__title">Report Approved ready for dispatch</h4>
	                    			<span class="m-widget1__desc">Total Report Approved ready for dispatch</span>
	                			</div>
	                    		<div class="col-sm-3" style="margin-top: 1.0em;">
	                    			<span class="m-widget1__number m--font-blue" id="status_14">' . $boxdata['c_status_14'] . '</span>
	                			</div>
	                		</div>
	                	</div>
	                	<div class="m-widget1__item">
	                    	<div class="row m-row--no-padding align-items-center">
	                    		<div class="col-sm-9">
	                    			<h4 class="m-widget1__title">Report Dispatched (Completed)</h4>
	                    			<span class="m-widget1__desc">Total Report Dispatched (Completed)</span>
	                			</div>
	                    		<div class="col-sm-3" style="margin-top: 1.0em;">
	                    			<span class="m-widget1__number m--font-blue" id="status_15">' . $boxdata['c_status_15'] . '</span>
	                			</div>
	                		</div>
	                	</div>
	                </div></div></div></div>';


			//$get_view_data = $this->leadM->getCaseLeadLimit();
			$get_view_data = $boxdata['case_row_data'];

			$html1 .= '<thead><tr><td><b>S. No.</b></td><td><b>Case Id</b></td><td style="width: 150px;"><b>Customer Name</b></td><td style="width: 150px;"><b>Email id</b></td><td style="width: 150px;"><b>Contact No.</b></td><td style="width: 150px;"><b>Created By / Position</b></td><td style="width: 100px;"><b>Assigned To</b></td><td style="width: 100px;"><b>Status</b></td><td style="width: 100px;"><b>Created Date</b></td><td style="width: 100px;"><b>Action</b></td></tr></thead><tbody>';
			$i = 1;
			foreach ($get_view_data as $value) {
				if ($i < 11) {
					$get_app = $this->w_user->getApproval($value['id']);
					$get_creater_name = $this->leadM->getCreaterName($value['created_by']);
					$get_creater_type = $this->leadM->getCreaterType($get_creater_name['user_type_id']);
					$get_assign_ba = $this->leadM->getLeadBA($value['id']);

					$get_case_row = $this->leadM->getCases($value['id']);
					// print_r($get_case_row);die;
					if (!empty($get_case_row)) {
						foreach ($get_case_row as $keylead_c => $valuelead_c) {
							$get_sur_data = $this->leadM->getSurveyor($valuelead_c['assign_to']);
							if (!empty($get_sur_data)) {
								$sur_id = $get_sur_data['id'];
								$url = base_url('admin/empM/editEmpAdmin/' . base64_encode($sur_id) . '/6');
								$sur_name = $get_sur_data['name'];
							} else if (!empty($get_assign_ba)) { // assign lead to ba 
								$url = 'javascript:void(0)';
								$sur_id = $get_assign_ba['assign_lead'];
								$sur_name = $get_assign_ba['name'] . ' (BA)';
							} else {
								$sur_name = 'N/A';
								$sur_id = '';
							}
							if ($valuelead_c['case_activity_status'] != 0) {
								$sw_id = $valuelead_c['case_activity_status'];
							} else {
								$sw_id = $value['status'];
							}

							if (!empty($value['added_by'])) {
								$get_cus_type = $this->w_user->getCustomerTypeInUsers($value['added_by']);
								$cus_type = $get_cus_type['cus_t_name'];
								$man_name = $get_cus_type['name'];
							} else {
								if ($all_data['user_type_id'] == 1) {
									$cus_type = 'Commercial Banks Customers';
								} else if ($all_data['user_type_id'] == 2) {
									$cus_type = 'NBFC Customers';
								} else if ($all_data['user_type_id'] == 3) {
									$cus_type = 'Corporate Customers';
								} else if ($all_data['user_type_id'] == 4) {
									$cus_type = 'Individual Private Customers';
								} else if ($all_data['user_type_id'] == 5) {
									$cus_type = 'IRP Customers';
								} else if ($all_data['user_type_id'] == 6) {
									$cus_type = 'ARC Customers';
								}

								$man_name = ucwords($all_data['Name']);
							}

							$get_status_data = $this->leadM->getStatus($sw_id);
							switch ($sw_id) {
								case '6':
									$status_button = '<div style="background: #9370DB;" class="label label-primary lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '7':
									$status_button = '<div style="" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '8':
									$status_button = '<div style="" class="label label-info lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '9':
									$status_button = '<div style="background: #00FA9A;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '10':
									$status_button = '<div style="background: #FF1493;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '11':
									$status_button = '<div style="background: #FF1493;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '12':
									$status_button = '<div style="background: #FF4500;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '13':
									$status_button = '<div style="background: #BDB76B;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '14':
									$status_button = '<div style="background: #FF00FF;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								case '15':
									$status_button = '<div style="background: #6A5ACD;" class="label label-success lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
									break;
								default:
									break;
							}





							$html1 .= '<tr id="' . $value['id'] . '" class="main_row"><td>' . $i . '</td><td>' . $valuelead_c['case_id'] . '</td><td>' . ucwords($value['customer_name']) . '</td><td>' . $value['co_persone_email'] . '</td><td>' . $value['co_persone_number'] . '</td><td>' . $get_creater_name['name'] . ' / ' . $man_name . '</td><td>';
							if (!empty($sur_id)) {
								$html1 .= '<a href="' . base_url('admin/empM/editEmpAdmin/' . base64_encode($sur_id) . '/6') . '">' . $sur_name . '</a>';
							} else {
								$html1 .= '' . $sur_name . '';
							}
							$html1 .= '</td><td id="act" style="padding-top: 10px;">' . $status_button . '</td>';
							$a = date('d-m-Y H:i:s', strtotime($value['created_at']));
							$html1 .= '<td>' . $a . '</td><td id="act" style="width: 100px;">';

							if ($valuelead_c['status'] != 15) {
								$html1 .= '<a href="' . base_url('admin/webU/editCase/' . base64_encode($value['id']) . '/' . base64_encode($valuelead_c['id'])) . '" id="edit_' . $valuelead_c['id'] . '" data-id="' . $valuelead_c['id'] . '"><button class="btn btn-primary" type="button" title="Edit Lead Details">View/Edit</button></a>';
							}
							$html1 .= '</td></tr>';
							$i++;
						}
					}
				}
			}

			$html1 .= '</tbody>';
		} else if ($val == 5) {
			$html .= '<div class="col-md-6"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">VIS Leads Data</h3><div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button><button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button></div>
                    </div><div class="box-body chart" style="padding: 0 0 19px 0;"><div class="m-widget1"><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for BA Assignment</h4><span class="m-widget1__desc">Total Pending for BA Assignment</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-brand" id="pen_for_ba">' . $boxdata['pen_for_ba'] . '</span></div></div></div><div class="m-widget1__item" ><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for Mandatory details</h4><span class="m-widget1__desc">Total Pending for Mandatory details</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning2" id="pen_for_qu">' . $boxdata['pen_for_qu'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for Quotation</h4><span class="m-widget1__desc">Total Pending for Quotation</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-purple" id="pen_for_adv">' . $boxdata['pen_for_adv'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Quotation Approval Awaited</h4><span class="m-widget1__desc">Total Quotation Approval Awaited</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-warning" id="pen_for_doc">' . $boxdata['pen_for_doc'] . '</span></div></div></div><div class="m-widget1__item"><div class="row m-row--no-padding align-items-center"><div class="col-sm-9"><h4 class="m-widget1__title">Pending for Advance Payment</h4><span class="m-widget1__desc">Total Pending for Advance Payment</span></div><div class="col-sm-3" style="margin-top: 1.0em;"><span class="m-widget1__number m--font-blue" id="pen_for_sa">' . $boxdata['pen_for_sa'] . '</span></div></div></div></div></div></div></div>';

			$user_type_id = $this->session->userdata('adminData')['user_type'];
			$designation_id = $this->session->userdata('designation_id');

			if ($user_type == '4') {
				$get_view_data = $this->w_user->getCaseDataAdminLimit();
			} else {
				if ($user_type_id == 5) {
					$get_view_data = $this->w_user->getLeadBA($userId);
				} else if ($user_type_id == 1) {
					if ($designation_id == 114) {
						$get_user_department = $this->w_user->getUserDepartment($userId);
						$department = $get_user_department['department'];
						if ($department == 5) {
							$get_view_data = $this->w_user->getLeadForSeniorCoordBA($userId);
						} else {
							$get_view_data = $this->w_user->getLeadForSeniorCoord($userId);
						}
					} else {
						$get_user_department = $this->w_user->getUserDepartment($userId);
						$department = $get_user_department['department'];
						if ($department == 3 || $department == 4 || $department == 5) {
							$get_view_data = $this->w_user->getLeadBA($userId);
						} else {
							$get_view_data = $this->w_user->getLeadCrByEmp($userId);
						}
					}
				} else {
					$get_view_data = $this->w_user->getLeadCrByCust($userId);
				}
			}

			$html1 .= '<thead><tr><td><b>S. No.</b></td><td><b>Lead ID</b></td><td><b>Customer Type</b></td><td><b>Manager Name</b></td><td><b>Customer Name</b></td><td><b>Coordinating Person Name</b></td><td><b>Email Id</b></td><td><b>Contact Number</b></td><td><b>Created By</b></td><td width="200"><b>Status</b></td><td><b>Created Date</b></td></tr></thead><tbody>';
			$i = 1;
			foreach ($get_view_data as $value) {
				$get_app = $this->w_user->getApproval($value['id']);
				$get_sur_data = $this->w_user->assignLead($value['id']);
				$get_status_data = $this->w_user->enqueryStatus($value['status']);

				switch ($value['status']) {
					case '1':
						$status_button = '<div style="" class="label label-info lbl status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;
					case '2':
						$status_button = '<div style="background: #9370DB;" class="label lbl label-primary status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;
					case '3':
						$status_button = '<div style="" class="label label-success lbl  status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;
					case '4':
						$status_button = '<div style="background: #00FA9A;" class="label lbl label-success status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;
					case '5':
						$status_button = '<div class="label lbl label-warning status_button">' . ucfirst($get_status_data['status_string']) . '</div>';
						break;
					default:
						$status_button = '<div style="" class="label lbl label-danger status_button">Cancel Enquiry</div>';
						break;
				}

				if ($value['share_link_or_not'] == 1) {
					$bk_clr = 'set_bg';
				} else {
					$bk_clr = '';
				}

				if ($get_app == 2 && $i < 11) {


					$html1 .= '<tr id="' . $value['id'] . '" class="main_row ' . $bk_clr . '"><td>' . $i . '</td><td>' . $value['lead_ids'] . '</td>';
					if (!empty($value['added_by'])) {
						$get_cus_type = $this->w_user->getCustomerTypeInUsers($value['added_by']);
						$cus_type = $get_cus_type['cus_t_name'];
						$man_name = $get_cus_type['name'];
					} else {
						if ($all_data['user_type_id'] == 1) {
							$cus_type = 'Commercial Banks Customers';
						} else if ($all_data['user_type_id'] == 2) {
							$cus_type = 'NBFC Customers';
						} else if ($all_data['user_type_id'] == 3) {
							$cus_type = 'Corporate Customers';
						} else if ($all_data['user_type_id'] == 4) {
							$cus_type = 'Individual Private Customers';
						}

						$man_name = ucwords($all_data['Name']);
					}

					if ($value['added_by'] != '') {
						$get_user_data = $this->w_user->getLeadUser($value['added_by']);
						$get_cr_data = $this->w_user->getLeadUser($value['behalf_of']);

						$get_usertype_data = $this->w_user->getUserType($get_user_data['user_type_id']);
						$c_name = $get_usertype_data['name'];
						$get_created_by_data = $this->w_user->getLeadUser($get_cr_data['id']);
						$created_by = $get_created_by_data['name'];
					} else {
						if ($value['created_by'] != '1') {
							$get_user_data = $this->w_user->getLeadUser($value['created_by']);

							$get_usertype_data = $this->w_user->getUserType($get_user_data['user_type_id']);
							$c_name = $get_usertype_data['name'];
							$get_created_by_data = $this->w_user->getLeadUser($get_user_data['id']);
							$created_by = $get_created_by_data['name'];
						} else {
							$c_name = 'Admin';
							$created_by = 'Admin';
						}
					}



					$html1 .= '<td>' . $c_name . '</td>';

					if ($value['cancel_reason'] == '') {
						$name = 'Admin';
					}
					$html1 .= '<td>' . ucwords($man_name) . '</td><td>' . ucwords($value['customer_name']) . '</td><td>' . ucwords($value['co_persone_name']) . '</td><td>' . $value['co_persone_email'] . '</td><td>' . $value['co_persone_number'] . '</td><td>' . ucwords($created_by) . '</td>';
					$html1 .= '<td id="act" width="200" style="padding-top: 10px;">' . $status_button . '</td>';
					$date_f = strtotime($value['created_at']);
					$d = $date_formate = date('d-m-Y H:i', $date_f);
					$html1 .= '<td width="100">' . $d . '</td></tr>';
					$i++;
				}
			}

			$html1 .= '</tbody>';
		}

		echo json_encode(array('result1' => $html, 'result2' => $html1));
	}

	public function PostSearchRequest()
	{
		// print_r($this->input->post());die;
		$sel_cat = $this->input->post('sel_cat');
		$sub_cat = $this->input->post('sub_cat');
		$search = $this->input->post('search');
		$from_date = $this->input->post('from_date');
		$to_date = $this->input->post('to_date');
		if ($sel_cat == '1') {
			if ($sub_cat == '1') {
				redirect(site_url('admin/userM/viewUser/' . base64_encode($sub_cat) . '?search=1&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date));
			} else if ($sub_cat == '2') {
				redirect(site_url('admin/userM/viewUser/' . base64_encode($sub_cat) . '?search=2&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date));
			} else if ($sub_cat == '3') {
				redirect(site_url('admin/userM/viewUser/' . base64_encode($sub_cat) . '?search=3&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date));
			} else if ($sub_cat == '4') {
				redirect(site_url('admin/userM/viewUser/' . base64_encode($sub_cat) . '?search=4&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date));
			}
		} else if ($sel_cat == '2') {
			if ($sub_cat == '1') {
				redirect(site_url('admin/resourceM/viewResourcesLists?search=1&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date));
			} else if ($sub_cat == '2') {
				redirect(site_url('admin/empM/viewNewUserLists?search=1&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date));
			}
		} else if ($sel_cat == '3') {
			redirect(site_url('admin/empM/viewNewAssLists?search=1&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date . '&sub_cat=' . $sub_cat));
		} else if ($sel_cat == '4') {
			redirect(site_url('admin/webU/viewCase?search=1&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date . '&sub_cat=' . $sub_cat));
		} else if ($sel_cat == '5') {
			redirect(site_url('admin/leadM/viewLeadsInAdmin?search=1&keyword=' . $search . '&from_date=' . $from_date . '&to_date=' . $to_date . '&sub_cat=' . $sub_cat));
		}
	}

	public function autoSuggestionKeywords()
	{
		$category 		= $this->input->post('category');
		$sub_category 	= $this->input->post('sub_category');
		$keyword 		= $this->input->post('keyword');

		$mergeAllSearchKeywords = array();
		$firstSearchArray = array();

		/*For Customer Keywords Suggestion*/
		if ($category == "1") {
			$tableArray = array('1' => 'users', '2' => 'user_details', '3' => 'customer_designation', '4' => 'bank_branch_master', '5' => 'bank_details', '6' => 'master_states', '7' => 'master_district');

			$columnArrayOne = array('1' => 'name', '2' => 'email', '3' => 'branch_email_id', '4' => 'aadhar_card', '5' => 'update_reason', '6' => 'employee_id');
			$whereConditionOne = array('id !=' => 1, 'user_type_id' => $sub_category, 'agree_terms_cond !=' => 0);

			$columnArrayTwo = array('1' => 'phone', '2' => 'cug_mobile', '3' => 'Desk_number', '4' => 'Board_number', '5' => 'pincode', '6' => 'company_name', '7' => 'gstin_no', '8' => 'company_email', '9' => 'comp_addr_address', '10' => 'comp_addr_pincode', '11' => 'address');
			$whereConditionTwo = array('status' => 1);

			$columnArrayThree = array('1' => 'name');
			$whereConditionThree = array();

			if ($sub_category == "1") {
				$columnArrayFourth = array('1' => 'branch_name', '2' => 'ifsc_code', '3' => 'branch_code', '4' => 'branch_official_email', '5' => 'branch_head_email', '6' => 'branch_address', '7' => 'location', '8' => 'branch_phone_no', '9' => 'branch_mobile_no');
				$whereConditionFourth = array('status' => 1);

				$columnArrayFifth = array('1' => 'bank_name', '2' => 'head_office_address', '3' => 'bank_phone_no', '4' => 'location');
				$whereConditionFifth = array('status' => 1);
			} else {
				$columnArrayFourth = array();
				$whereConditionFourth = array();

				$columnArrayFifth = array();
				$whereConditionFifth = array();
			}

			$columnArraySixth = array('1' => 'name');
			$whereConditionSixth = array('status' => 1);

			$columnArraySeventh = array('1' => 'name');
			$whereConditionSeventh = array('status' => 1);


			$columnArray = array('1' => $columnArrayOne, '2' => $columnArrayTwo, '3' => $columnArrayThree, '4' => $columnArrayFourth, '5' => $columnArrayFifth, '6' => $columnArraySixth, '7' => $columnArraySeventh);
			$whereConditionArray = array('1' => $whereConditionOne, '2' => $whereConditionTwo, '3' => $whereConditionThree, '4' => $whereConditionFourth, '5' => $whereConditionFifth, '6' => $whereConditionSixth, '7' => $whereConditionSeventh);

			for ($i = 1; $i <= count($tableArray); $i++) {
				for ($j = 1; $j <= count($columnArray[$i]); $j++) {
					$query_first_search = $this->db

						->select('*')
						->from($tableArray[$i])
						->like($columnArray[$i][$j], $keyword)
						->where($whereConditionArray[$i])
						->get()
						->result_array();

					foreach ($query_first_search as $query_data_first) {
						if (!in_array($query_data_first[$columnArray[$i][$j]], $firstSearchArray)) {
							array_push($firstSearchArray, $query_data_first[$columnArray[$i][$j]]);
						}
					}
				}
			}

			$mergeAllSearchKeywords = array_unique($firstSearchArray);
		}

		/*For Employee Keywords Suggestion*/
		if ($category == "2") {
			$tableArray = array('1' => 'users', '2' => 'employee_details', '3' => 'department');

			$columnArrayOne = array('1' => 'name', '2' => 'email', '3' => 'branch_email_id', '4' => 'aadhar_card', '5' => 'update_reason', '6' => 'employee_id', '7' => 'aadhar_card');
			$whereConditionOne = array('id !=' => 1, 'user_category_id' => 1, 'resource_status' => $sub_category);

			$columnArrayTwo = array('1' => 'trainer_name', '2' => 'cus_mobile_no', '3' => 'assign_email', '4' => 'leave_reason');
			$whereConditionTwo = array('status !=' => 3);

			$columnArrayThree = array('1' => 'name');
			$whereConditionThree = array('status' => 1);

			$columnArray = array('1' => $columnArrayOne, '2' => $columnArrayTwo, '3' => $columnArrayThree);
			$whereConditionArray = array('1' => $whereConditionOne, '2' => $whereConditionTwo, '3' => $whereConditionThree);

			for ($i = 1; $i <= count($tableArray); $i++) {
				for ($j = 1; $j <= count($columnArray[$i]); $j++) {
					$query_first_search = $this->db

						->select('*')
						->from($tableArray[$i])
						->like($columnArray[$i][$j], $keyword)
						->where($whereConditionArray[$i])
						->get()
						->result_array();

					foreach ($query_first_search as $query_data_first) {
						if (!in_array($query_data_first[$columnArray[$i][$j]], $firstSearchArray)) {
							array_push($firstSearchArray, $query_data_first[$columnArray[$i][$j]]);
						}
					}
				}
			}

			$mergeAllSearchKeywords = array_unique($firstSearchArray);
		}

		/*For Associate Keywords Suggestion*/
		if ($category == "3") {
			$tableArray = array('1' => 'users', '2' => 'associate_details');

			$columnArrayOne = array('1' => 'name', '2' => 'email', '3' => 'branch_email_id', '4' => 'aadhar_card', '5' => 'update_reason', '6' => 'employee_id', '7' => 'aadhar_card');

			if (!empty($sub_category)) {
				$whereConditionOne = array('id !=' => 1, 'user_category_id' => 3, 'employee_status' => $sub_category);
			} else {
				$whereConditionOne = array('id !=' => 1, 'user_category_id' => 3);
			}

			$columnArrayTwo = array('1' => 'personal_mobile_no');
			$whereConditionTwo = array('status !=' => 3);

			$columnArray = array('1' => $columnArrayOne, '2' => $columnArrayTwo);
			$whereConditionArray = array('1' => $whereConditionOne, '2' => $whereConditionTwo);

			for ($i = 1; $i <= count($tableArray); $i++) {
				for ($j = 1; $j <= count($columnArray[$i]); $j++) {
					$query_first_search = $this->db

						->select('*')
						->from($tableArray[$i])
						->like($columnArray[$i][$j], $keyword)
						->where($whereConditionArray[$i])
						->get()
						->result_array();

					foreach ($query_first_search as $query_data_first) {

						if (!in_array($query_data_first[$columnArray[$i][$j]], $firstSearchArray)) {
							array_push($firstSearchArray, $query_data_first[$columnArray[$i][$j]]);
						}
					}
				}
			}

			$mergeAllSearchKeywords = array_unique($firstSearchArray);
		}

		/*For Leads Keywords Suggestion*/
		if ($category == "4") {
			$tableArray = array('1' => 'leads', '2' => 'customer_usertype', '3' => 'reporting_service', '4' => 'users', '5' => 'set_lead_activity_status');

			$columnArrayOne = array('1' => 'customer_name', '2' => 'co_persone_name', '3' => 'co_persone_number', '4' => 'lead_ids', '5' => 'co_persone_email');
			if (!empty($sub_category)) {
				$whereConditionOne = array('status' => $sub_category);
			} else {
				$whereConditionOne = array();
			}


			$columnArrayTwo = array('1' => 'name');
			$whereConditionTwo = array('status' => 1);

			$columnArrayThree = array('1' => 'name');
			$whereConditionThree = array('status !=' => 3);

			$columnArrayFourth = array('1' => 'name');
			$whereConditionFourth = array('resource_status' => 1);

			$columnArrayFifth = array('1' => 'status_string');
			$whereConditionFifth = array('status !=' => 3);


			$columnArray = array('1' => $columnArrayOne, '2' => $columnArrayTwo, '3' => $columnArrayThree, '4' => $columnArrayFourth, '5' => $columnArrayFifth);
			$whereConditionArray = array('1' => $whereConditionOne, '2' => $whereConditionTwo, '3' => $whereConditionThree, '4' => $whereConditionFourth, '5' => $whereConditionFifth);

			for ($i = 1; $i <= count($tableArray); $i++) {
				for ($j = 1; $j <= count($columnArray[$i]); $j++) {
					$query_first_search = $this->db
						->select('*')
						->from($tableArray[$i])
						->like($columnArray[$i][$j], $keyword)
						->where($whereConditionArray[$i])
						->get()
						->result_array();

					foreach ($query_first_search as $query_data_first) {

						if (!in_array($query_data_first[$columnArray[$i][$j]], $firstSearchArray)) {
							array_push($firstSearchArray, $query_data_first[$columnArray[$i][$j]]);
						}
					}
				}
			}

			$mergeAllSearchKeywords = array_unique($firstSearchArray);
		}

		//print_r($mergeAllSearchKeywords);exit;
		$keywordHtml = "";
		$keywordHtml .= '<ul id="keyword_list">';
		foreach ($mergeAllSearchKeywords as $mergeAllSearchKeywordsData) {
			$selectKeyword = "'" . $mergeAllSearchKeywordsData . "'";
			$keywordHtml .= '<li onclick="selectKeyword(' . $selectKeyword . ');">' . $mergeAllSearchKeywordsData . '</li>';
		}
		$keywordHtml .= '</ul>';
		echo $keywordHtml;
	}

	/* check mail if mail already exist */
	public function checkEmailExist()
	{
		if ($this->input->post()) {
			$email = trim($this->input->post('email'));

			$query = $this->customerM->checkMail($email);

			if (!empty($query)) {
				$msg = 'This Email Id already exist into the system';
				$ret_val = $msg;
			} else {
				$ret_val = 0;
			}
			echo json_encode($ret_val);
			exit;
		}
	}

	public function checkCBCEmailExist()
	{
		if ($this->input->post()) {
			$email = trim($this->input->post('email'));
			$cid = trim($this->input->post('cid'));

			$query = $this->customerM->checkCBCMail($email, $cid);

			if (!empty($query)) {
				$msg = 'This Email Id already exist into the system';
				$ret_val = $msg;
			} else {
				$ret_val = 0;
			}
			echo json_encode($ret_val);
			exit;
		}
	}


	public function showNotification()
	{
		$id = trim($this->input->post('id'));
		$query = $this->customerM->updateNotification($id);
		echo 1;
		die;
	}




	/* bank detals */
	public function getBranchDetail()
	{
		if ($this->input->post()) {
			$branch_id 	= $this->input->post('id');
			$get_detail = $this->customerM->getBanks($branch_id);
			$bank_id 	= $get_detail['bank_id'];
			$ifsc_code 	= $get_detail['ifsc_code'];

			$get_details = $this->customerM->getBankBranch($bank_id);

			$getCreditEmail = $this->customerM->getCreditEmail($ifsc_code);
			$count = count($getCreditEmail);
			if ($count == 1) {
				// $branch_text = '<div  class="col-xs-6"><label for="exampleInputEmail1">New Credit Team / Branch Email Id <span class="red">*</span></label></div><div  class="col-xs-6" id="credit_email"><input type="email" class="form-control" name="credit_email" id="credit_email" placeholder="New Credit Team / Branch Email Id" value="'.$getCreditEmail['branch_official_email'].'" required><span id="err_credit_email" class="user_err"></span></div>';

				$branch_text = '<div  class="col-xs-6"><label for="exampleInputEmail1">New Credit Team / Branch Email Id <span class="red">*</span></label></div><div class="col-xs-6"><select data-live-search="true" data-live-search-style="startsWith" class="form-control selectpicker" name="credit_email_id" id="credit_email_id" onchange="sendOTP()"><option value="">Please Select Credit/Branch Email ID</option>';
				foreach ($getCreditEmail as $key => $value) {
					$branch_text .= '<option value="' . $value['id'] . '">' . $value['branch_official_email'] . '</option>';
				}

				$branch_text .= '</select><span id="err_credit_email_id" class="user_err"></span></div>';
			} else {
				$branch_text = '<div  class="col-xs-6"><label for="exampleInputEmail1">New Credit Team / Branch Email Id <span class="red">*</span></label></div><div class="col-xs-6"><select data-live-search="true" data-live-search-style="startsWith" class="form-control selectpicker" name="credit_email_id" id="credit_email_id" onchange="sendOTP()"><option value="">Please Select Credit/Branch Email ID</option>';
				foreach ($getCreditEmail as $key => $value) {
					$branch_text .= '<option value="' . $value['id'] . '">' . $value['branch_official_email'] . '</option>';
				}

				$branch_text .= '</select><span id="err_credit_email_id" class="user_err"></span></div>';
			}

			$set_html = '';
			$set_html .= '<div class="col-sm-12 col-xs-12 divPadding"><div class="col-xs-6 col-sm-6 divPadding set_bottom_p"><div class="col-xs-12 col-sm-12"><b>Name: </b>' . $get_details['bank_name'] . ' (' . $get_details['branch_name'] . ')</div></div><div class="col-xs-6 col-sm-6 divPadding set_bottom_p"><div class="col-xs-12 col-sm-12"><b>Address: </b>' . $get_details['location'] . '</div></div></div><input type="hidden" id="branch_e_id" name="branch_e_id" value="' . $get_details['branch_official_email'] . '">';

			echo json_encode(array("set_html" => $set_html, "branch_text" => $branch_text));
			// $arr =  { "set_html": $set_html, "domain" : $get_details['bank_domain']};
			// print_r($arr);
			die();
		}
	}


	public function setBranchOTP()
	{
		if ($this->input->post()) {
			$user_id 	= $this->input->post('user_id');
			$credit_email_id  = $this->input->post('credit_email_id');
			$otp_code 	= mt_rand(100000, 999999);
			$this->db->where(array('id' => $user_id))->update('users', array('otp_code' => $otp_code));

			$credit_email_detail = $this->db
				->select('*')
				->from('bank_branch_master')
				->where(array('id' => $credit_email_id))
				->get()
				->row_array();

			$manager_detail = $this->db
				->select('users.name, user_details.bank_id')
				->from('users')
				->join('user_details', 'user_details.user_id = users.id')
				->where(array('users.id' => $user_id))
				->get()
				->row_array();

			$credit_email = $credit_email_detail['branch_official_email'];

			$old_branch_detail = $this->db
				->select('*')
				->from('bank_branch_master')
				->where(array('id' => $manager_detail['bank_id']))
				->get()
				->row_array();

			$get_mail = $this->customerM->getOTPMail('36');

			$vars = array(
				'[$MANAGER_NAME]'  		=> ucwords($manager_detail['name']),
				'[$OLD_IFSC_CODE]'  	=> $old_branch_detail['ifsc_code'],
				'[$OLD_BRANCH_NAME]'  	=> ucwords($old_branch_detail['branch_name']),
				'[$NEW_IFSC_CODE]'  	=> $credit_email_detail['ifsc_code'],
				'[$NEW_BRANCH_NAME]'  	=> ucwords($credit_email_detail['branch_name']),
				'[$OTP_CODE]'  			=> $otp_code
			);

			$msg 		= strtr($get_mail['body'], $vars);
			$sub 		= $get_mail['subject'];

			$sign =  $msg;
			$sign .= $get_mail['content'];

			// $this->sendEmail($credit_email, $sign, $sub, $get_mail['send_to']);

			echo $otp_code;
		}
	}


	public function verifyBranchOTP()
	{
		if ($this->input->post()) {
			$user_id 	= $this->input->post('user_id');
			$credit_email_id  = $this->input->post('credit_email_id');
			$otp 	= $this->input->post('otp');

			$verify_otp = $this->db
				->select('*')
				->from('users')
				->where(array('id' => $user_id, 'otp_code' => $otp))
				->get()
				->row_array();

			$old_branch = $this->db
				->select('*')
				->from('user_details')
				->where(array('user_id' => $user_id))
				->get()
				->row_array();



			if (!empty($verify_otp)) {
				// echo 1;die;
				// $credit_email_detail = $this->db
				//                 ->select('*')
				//                 ->from('bank_branch_master')
				//                 ->where(array('id' => $credit_email_id))
				//                 ->get()
				//                 ->row_array(); 



				// $this->db->where(array('id' => $user_id))->update('users', array('branch_email_id' => $credit_email_detail['branch_official_email']));

				// $this->db->where(array('user_id' => $user_id))->update('user_details', array('bank_id' => $credit_email_id));

				// $this->db->where(array('created_by' => $user_id, 'added_by' => $user_id))->update('leads', array('created_by' => '', 'added_by' => '', 'behalf_of' => '', 'old_added_by' => $user_id, 'customer_old_branch' => $old_branch['bank_id']));

				// $this->db->where(array('added_by' => $user_id))->update('leads', array('added_by' => '', 'behalf_of' => '', 'old_added_by' => $user_id, 'customer_old_branch' => $old_branch['bank_id']));

				$res['msg'] = 'OTP Verified..';
				$res['res_code'] = 1;
			} else {
				$res['msg'] = 'OTP Not Verified! Try Again';
				$res['res_code'] = 2;
			}

			echo json_encode($res);
		}
	}

	public function updateCustomer()
	{
		$user_id = base64_decode($this->uri->segment(4, 0));
		$branch_id = base64_decode($this->uri->segment(5, 0));
		$data['user_id'] = $user_id;
		$data['branch_id'] = $branch_id;
		$get_user_type = $this->customerM->selectData(1);
		$data['user_type'] = $get_user_type;
		$sub = "Account verification notification";
		$get_countries = $this->customerM->get_Countries();
		$data['get_countries'] = $get_countries;

		$data['get_ifsc'] = $this->customerM->getIFSCCode();

		$data['user_data'] = $this->customerM->userDetails($user_id);
		$data['user_details'] = $this->db
			->select('*')
			->from('user_details')
			->where(array('user_id' => $user_id))
			->get()
			->row_array();

		// $get_detail = $this->customerM->getBanks($data['user_details']['bank_id']);
		$old_branch_id = $data['user_details']['bank_id'];
		$data['old_branch_id'] = $old_branch_id;

		$data['get_details'] = $this->customerM->getBranchById($branch_id);

		$data['get_leads'] = $this->customerM->getPrevBranchLeads($user_id, $old_branch_id);
		$data['get_leads_count'] = count($data['get_leads']);

		$data['get_cases_leads'] = $this->customerM->getPrevBranchCasesLeads($user_id, $old_branch_id);
		$data['get_cases_leads_count'] = count($data['get_cases_leads']);


		$this->load->view('admin/customer_pages/update_cbc', $data);

		if ($this->input->post()) {


			$name			= trim($this->input->post('form_1_name'));
			$email 			= trim($this->input->post('man_email1'));
			$branch_email 	= trim($this->input->post('branch_e_id'));
			$cid 			= trim($this->input->post('cid'));
			$user_official 			= $this->input->post('credit_email1');
			$new_branch_id 	= trim($this->input->post('new_branch_id'));
			$new_credit_team_email	= trim($this->input->post('new_credit_team_email'));


			$data = array(
				'name'		 			=> $name,
				'email' 				=> $email,
				'branch_email_id'		=> $new_credit_team_email,
				'updated_at'			=> date('Y-m-d H:i:s'),
				'agree_terms_cond'		=> '1'
			);

			$save_data = $this->customerM->updateData($cid, $data, 'id', 'users');

			$team_id				= $this->input->post('team_no');
			$code 					= $this->input->post('code');
			$phone 					= $this->input->post('personal_no1');
			$cug_mobile 			= $this->input->post('cug_no1');
			$designation_id			= $this->input->post('designation');
			$desk_number 			= $this->input->post('desk_no');
			$board_number 			= $this->input->post('board_no');
			$team_no 				= $this->input->post('team_no');



			$data1 = array(
				'bank_id'				=> $new_branch_id,
				'team_id'				=> $team_id,
				'user_official_email' 	=> $user_official,
				'phone'					=> $phone,
				'designation_id'		=> $designation_id,
				'cug_mobile'			=> $cug_mobile,
				'desk_number'			=> $desk_number,
				'board_number'      	=> $board_number,
				'team_id'               => $team_no,
				'status'        		=> '1',
				'updated_at'			=> date('Y-m-d H:i:s'),

			);
			$save_option = $this->customerM->updateData($cid, $data1, 'user_id', 'user_details');
			echo 1;

			// if ($save_data > 0 ){ 

			// 	$message = '';
			// 	$confirmUrl = site_url('admin/welcome/confirmEmail?user_id='.base64_encode($save_data).'&email='.$user_official);

			// 	$get_mail = $this->customerM->getOTPMail('2');

			// 	$vars = array(
			//                             '[$NAME]'  		=> ucwords($name),
			//                             '[$URL]'  		=> $confirmUrl

			//                             ); 

			//        $msg 		= strtr($get_mail['body'], $vars);
			//        $sub 		= $get_mail['subject'];

			//        $sign =  $msg;
			// 	$sign .= $get_mail['content'];

			// 	$this->sendEmail($user_official, $sign, $sub, $get_mail['send_to']);

			// 	$get_mail1 = $this->customerM->getOTPMail('3');

			// 	$vars1 = array(
			//                             '[$NAME]'  		=> ucwords($name)

			//                             ); 

			//        $msg1 		= strtr($get_mail1['body'], $vars1);
			//        $sub2 		= $get_mail1['subject'];

			//        $sign2 =  $msg1;
			// 	$sign2 .= $get_mail1['content'];

			// 	$this->sendEmail($branch_email, $sign2, $sub2, $get_mail1['send_to']);

			// 	$message = '<div class="success_msg" id="secc_msg"><div class="col-xs-12 set_div_msg">You have Successfully Registered in Valuation Intelligence System… </div></div>';
			//     			$this->session->set_flashdata('message', $message);   
			//        redirect('admin/welcome/updateSuccess');
			//    }
		}
	}


	public function getCustomerManager()
	{
		if ($this->input->post()) {
			$branch_id 	= $this->input->post('branch_id');
			$cid 		= $this->input->post('cid');
			$customers 	= $query = $this->db
				->select('users.*')
				->from('users')
				->join('user_details', 'user_details.user_id = users.id')
				->where(array('user_details.bank_id' => $branch_id, 'users.user_type_id' => 1, 'users.status' => 1, 'users.id!=' => $cid))
				->get()->result_array();
			$html = '<option value="">Select Customer</option>';
			foreach ($customers as $key => $value) {
				$html .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
			}
			echo $html;
		}
	}


	public function finalBranchUpdate()
	{
		if ($this->input->post()) {
			$new_branch_id 			= $this->input->post('new_branch_id');
			$cid 					= $this->input->post('cid');
			$new_credit_team_email 	= $this->input->post('new_credit_team_email');
			$branch_e_id 			= $this->input->post('branch_e_id');

			$form_1_name 			= $this->input->post('form_1_name');
			$designation 			= $this->input->post('designation');
			$team_no 				= $this->input->post('team_no');
			$man_email1 			= $this->input->post('man_email1');

			$credit_email1 			= $this->input->post('credit_email1');
			$cug_no1 				= $this->input->post('cug_no1');
			$personal_no1 			= $this->input->post('personal_no1');
			$desk_no 				= $this->input->post('desk_no');

			$board_no 				= $this->input->post('board_no');
			$lead_id 				= $this->input->post('lead_id');
			$new_manager_id 		= $this->input->post('new_manager_id');
			$new_manager_reason 	= $this->input->post('new_manager_reason');

			$old_branch_id 			= $this->input->post('old_branch_id');


			$data = array(
				'name'		 			=> $form_1_name,
				'email' 				=> $man_email1,
				'branch_email_id'		=> $new_credit_team_email,
				'updated_at'			=> date('Y-m-d H:i:s'),
				'agree_terms_cond'		=> '1'
			);

			$save_data = $this->customerM->updateData($cid, $data, 'id', 'users');

			$data1 = array(
				'bank_id'				=> $new_branch_id,
				'user_official_email' 	=> $credit_email1,
				'phone'					=> $personal_no1,
				'designation_id'		=> $designation,
				'cug_mobile'			=> $cug_no1,
				'desk_number'			=> $desk_no,
				'board_number'      	=> $board_no,
				'team_id'               => $team_no,
				'status'        		=> '1',
				'updated_at'			=> date('Y-m-d H:i:s'),

			);
			$save_option = $this->customerM->updateData($cid, $data1, 'user_id', 'user_details');

			foreach ($lead_id as $key => $value) {
				$lead_val = $query = $this->db->select('*')->from('leads')->where(array('id' => $value))->get()->row_array();

				$manager_det = $query = $this->db->select('*')->from('users')->where(array('id' => $cid))->get()->row_array();
				$manager_name = $manager_det['name'];

				if ($lead_val['created_by'] == $cid) {
					$this->db->where(array('id' => $value))->update('leads', array('created_by' => $new_manager_id[$key], 'added_by' => $new_manager_id[$key], 'behalf_of' => $new_manager_id[$key]));
				} else {
					$this->db->where(array('id' => $value))->update('leads', array('added_by' => $new_manager_id[$key]));
				}

				$data2 = array(
					'old_customer' 	=> $cid,
					'new_customer' 	=> $new_manager_id[$key],
					'lead_id' 	   	=> $value,
					'old_branch' 	=> $old_branch_id,
					'new_branch' 	=> $new_branch_id,
					'remark' 		=> $new_manager_reason[$key],
					'status' 	   	=> '1',
					'created_at' 	=> date('Y-m-d H:i:s'),
					'updated_at'	=> date('Y-m-d H:i:s'),
				);

				$query = $this->db->insert('case_reassignment_history', $data2);

				$n_url = base_url('admin/webU/editCase/' . base64_encode($value) . '?pid=MQ==');

				$text = '<a class="user_notification" href="' . $n_url . '"><b>VIS0' . $value . '</b>, is assigned to you by ' . ucwords($manager_name) . '</a>';

				$n_arr = array(
					'emp_id' 		=> $new_manager_id[$key],
					'res_id' 		=> $value,
					'notification_type'	=> 3,  // for assign lead to new customer
					'text' 			=> $text,
					'status' 		=> '1',
					'created_at' 	=> date('Y-m-d H:i:s'),
					'updated_at'	=> date('Y-m-d H:i:s')

				);

				$this->db->insert('user_notification', $n_arr);
			}

			echo 1;
		}
	}

	public function chkPincode()
	{
		if ($this->input->post()) {
			$num 			= trim($this->input->post('num'));
			$get_pincode = $this->customerM->chkPincode($num);
			echo $get_pincode;
			exit();
		}
	}

	//land_and_use
	public function get_land_and_use()
	{
		$category_id = $this->input->post('category_id');
		$category = $this->db->select('category')->from('tbl_land_use_change_law_category')->where(array('id' => $category_id))->get()->row_array();
		$query = $this->db->query("SELECT * FROM `tbl_referene_state` WHERE `category_id`='" . $category_id . "' GROUP BY reference_state_id");
		$refrence_no = $query->num_rows();
		$data = $query->result_array();
		if ($refrence_no == 1) {
			$query2 = $this->db->query("SELECT * FROM `tbl_referene_state` WHERE `reference_state_id`='" . $data[0]['reference_state_id'] . "'");
			$title_no = $query2->num_rows();
			$titles = $query2->result_array();
			if ($title_no == 1) {
				$result['action'] = 'open';
				$result['status'] = 1;
				if ($titles[0]['types'] == 'urls') {
					$paths = $titles[0]['paths'];
				} elseif ($titles[0]['types'] == 'pdf') {
					$paths = base_url() . 'assets/uploads/land_use_change/' . $titles[0]['paths'];
				}
				$result['data'] = $paths;
			} else if ($title_no > 1) {
				$result['action'] = 'open';
				$result['status'] = 2;
				$html = '<div class="row m1" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;">' . $category['category'] . '</span></div></div>';
				$html .= '<div class="row m1" style="padding: 11px;">';
				foreach ($data as $value) {
					$html .= '<div class="col-sm-4" style="font-size: 13px;">';
					$html .= '<span id="open" onclick="get_category_reference_data(`' . $value['term_no'] . '`,`' . $category['category'] . '`,`' . $value['reference_state_id'] . '`);" style="color: #154EBF; padding: 2px; cursor: pointer; font-size: 13px;">' . $value['reference_state_id'] . '</span>';
					$html .= '</div>';
				}
				$html .= '</div>';
				$result['data'] = $html;
			} else {
				$result['action'] = 'no record';
				$result['status'] = 3;
				$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div>';
			}
		} else if ($refrence_no > 1) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;">' . $category['category'] . '</span></div></div>';
			$html .= '<div class="row" style="padding: 11px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-4" style="font-size: 13px;">';
				$html .= '<span onclick="get_category_reference_data(`' . $value['term_no'] . '`,`' . $category['category'] . '`,`' . $value['reference_state_id'] . '`);" style="color: #154EBF; padding: 2px; cursor: pointer; font-size: 13px;">' . $value['reference_state_id'] . '</span>';
				$html .= '</div>';
			}
			$html .= '</div>';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;

			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div>';
		}
		echo json_encode($result);
	}

	public function get_category_reference_data()
	{
		$category = $this->input->post('category');
		$reference_state_id = $this->input->post('reference_state_id');
		$term_no = $this->input->post('term_no');
		$query2 = $this->db->query("SELECT * FROM `tbl_referene_state` WHERE `term_no`='" . $term_no . "'");
		$title_no = $query2->num_rows();
		$data = $query2->result_array();
		if ($title_no > 0) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;">' . $category . ' >> ' . $reference_state_id . '</span></div></div>';
			$html .= '<div class="row" style="padding: 10px;">';
			foreach ($data as $value) {
				if ($value['types'] == 'urls') {
					$paths = $value['paths'];
				} elseif ($value['types'] == 'pdf') {
					$paths = base_url() . 'assets/uploads/land_use_change/' . $value['paths'];
				}

				$html .= '<div class="col-sm-3" style="font-size: 13px;">';
				$html .= '<a target="_blank" href="' . $paths . '">';
				$html .= '<span style="color: #154EBF; padding: 2px; cursor: pointer; font-size: 13px; padding: 10px;">' . $value['title'] . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div>';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div>';
		}
		echo json_encode($result);
	}

	public function get_login_other_state()
	{
		$menu_id = $this->input->post('menu_id');
		$query = $this->db->query("SELECT * FROM `tbl_other_login_menu_state_data` WHERE `menu_id`='" . $menu_id . "' GROUP BY state_id");
		$refrence_no = $query->num_rows();
		$data = $query->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search State</label></div><div class="col-md-4 col-xs-10 d-flex p0">
                <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data(this.value);" placeholder="Search state...">
                  <datalist id="ssak_data_list">
                     <option value="">Select State</option>';
		foreach ($data as $key => $val) {
			$search_data .= '<option value="' . $val['state_id'] . '">';
		}
		$search_data .= '</datalist></div><div class="col-md-1 col-xs-2 p0">
                  <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                  <i class="fa fa-search red-colr fs-16"> </i>
                  </button></div>';
		if ($refrence_no > 0) {
			$html = '';
			foreach ($data as $key => $val) {
				$html .= '<div class="col-md-4" style="margin: -8px 0 0 0;"><p style="font-size: 13px; color: #999;"><a href="#" onclick="get_login_other_state_data(`' . $menu_id . '`,`' . $val['term_no'] . '`)">' . $val['state_id'] . '</a></p></div>';
			}
		} else {
			$html = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div>';
		}
		$data['search_data'] = $search_data;
		$data['data'] = $html;
		echo json_encode($data);
	}

	public function get_login_other_state_data()
	{
		$menu_id = $this->input->post('menu_id');
		$term_no = $this->input->post('term_no');
		$query2 = $this->db->query("SELECT * FROM `tbl_other_login_menu_state_data` WHERE `term_no`='" . $term_no . "' AND `menu_id`='" . $menu_id . "'");
		$title_no = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search State</label></div><div class="col-md-4 col-xs-10 d-flex p0">
                <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data(this.value);" placeholder="Search state...">
                  <datalist id="ssak_data_list">
                     <option value="">Select State</option>';
		foreach ($data as $key => $val) {
			$search_data .= '<option value="' . $val['title'] . '">';
		}
		$search_data .= '</datalist></div><div class="col-md-1 col-xs-2 p0">
                  <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                  <i class="fa fa-search red-colr fs-16"> </i>
                  </button></div>';


		if ($title_no > 0) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;">' . $data[0]['state_id'] . '</span></div></div>';
			$html .= '<div class="row" style="padding: 10px;">';
			foreach ($data as $value) {
				if ($value['types'] == 'urls') {
					$paths = $value['paths'];
				} elseif ($value['types'] == 'pdf') {
					$paths = base_url() . 'assets/uploads/land_use_change/' . $value['paths'];
				}

				$html .= '<div class="col-sm-3" style="font-size: 13px;">';
				$html .= '<a target="_blank" href="' . $paths . '">';
				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $value['title'] . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_login_other_state_model(`' . $menu_id . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_login_other_state_model(`' . $menu_id . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}


	//=================03-05-2020==========

	public function get_state_base_submenu_details()
	{
		$menu_id = $this->input->post('id');
		$title = $this->input->post('title');
		$query2 = $this->db->query("SELECT tbl_submenu_details.`id`,tbl_submenu_details.`category_id`,master_states.`name` FROM `tbl_submenu_details` INNER JOIN master_states ON  tbl_submenu_details.`category_id`=master_states.`id` WHERE tbl_submenu_details.`submenu_id`='" . $menu_id . "' AND tbl_submenu_details.`status`='1' ORDER BY master_states.`name` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"></span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$row = $this->db->get_where('master_states', array('id' => $value['category_id']))->row();
				$html .= '<div class="';
				if ($num_rows == 2) {
					$html .= 'col-sm-6';
				} else {
					$html .= 'col-sm-3';
				}
				$html .= ' " style="font-size: 13px;">';
				$html .= '<a class="btn" onclick="get_state_base_level_one(' . $value['id'] . ',`' . $title . '`,`' . $row->name . '`,' . $menu_id . ');" href="javascript:void(0);">';
				$html .= '<span id="open_now_one" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $row->name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr>';
			$result['data'] = $html;
		} else if ($num_rows > 1) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"></span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$row = $this->db->get_where('master_states', array('id' => $value['category_id']))->row();
				$html .= '<div class="';
				if ($num_rows == 2) {
					$html .= 'col-sm-6';
				} else {
					$html .= 'col-sm-3';
				}
				$html .= ' " style="font-size: 13px;">';
				$html .= '<a class="btn" onclick="get_state_base_level_one(' . $value['id'] . ',`' . $title . '`,`' . $row->name . '`,' . $menu_id . ');" href="javascript:void(0);">';
				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $row->name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr>';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr>';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function get_state_base_level_one()
	{
		$submenu_id = $this->input->post('submenu_id');
		$title = $this->input->post('title');
		$sub_title = $this->input->post('sub_title');
		$menu_id = $this->input->post('menu_id');
		$query2 = $this->db->query("SELECT * FROM `tbl_menu_level_one` WHERE  `sub_menu_category_id`='" . $submenu_id . "' AND `status`='1' ORDER BY `submenu_state_title` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $title . ' >> ' . $sub_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3" style="font-size: 13px;">';
				if ($value['type'] == 'distric') {
					$row = $this->db->get_where('master_district', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_state_base_level_two(' . $value['id'] . ',`' . $title . '`,`' . $sub_title . '`,`' . $name . '`,`' . $submenu_id . '`,`' . $menu_id . '`);" href="javascript:void(0);">';
				} else {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn" ' . $m . ' href="' . $paths . '">';
				}
				$html .= '<span id="open_now_two" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_state_base_model(`' . $menu_id . '`,`' . $title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else if ($num_rows > 1) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $title . ' >> ' . $sub_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3" style="font-size: 13px;">';
				if ($value['type'] == 'distric') {
					$row = $this->db->get_where('master_district', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_state_base_level_two(' . $value['id'] . ',`' . $title . '`,`' . $sub_title . '`,`' . $name . '`,`' . $submenu_id . '`,`' . $menu_id . '`);" href="javascript:void(0);">';
				} else {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						//$m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);'; //$value['pdf_url'];
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn"  ' . $m . ' href="' . $paths . '">';
				}
				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_state_base_model(`' . $menu_id . '`,`' . $title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_state_base_model(`' . $menu_id . '`,`' . $title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function get_state_base_level_two()
	{
		$submenu_id = $this->input->post('submenu_id');
		$title = $this->input->post('title');
		$sub_title = $this->input->post('sub_title');
		$chield_title = $this->input->post('chield_title');
		$menu_id = $this->input->post('menu_id');
		$fmenu_id = $this->input->post('fmenu_id');
		$query2 = $this->db->query("SELECT * FROM `tbl_menu_level_two` WHERE  `sub_menu_category_id`='" . $submenu_id . "' AND `status`='1' ORDER BY `submenu_state_title` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $title . ' >> ' . $sub_title . ' >> ' . $chield_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3" style="font-size: 13px;">';

				$name = $value['submenu_state_title'];
				$m = 'target="_blank"';
				if ($value['type'] == 'url') {
					// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
					// $paths = $value['pdf_url'];
					$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
					$paths = 'javascript:void(0);';
				} elseif ($value['type'] == 'pdf') {
					$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
				}
				$html .= '<a class="btn" ' . $m . ' href="' . $paths . '">';
				$html .= '<span id="open_now_three" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_state_base_level_one(`' . $menu_id . '`,`' . $title . '`,`' . $sub_title . '`,`' . $fmenu_id . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else if ($num_rows > 1) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $title . ' >> ' . $sub_title . ' >> ' . $chield_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3" style="font-size: 13px;">';

				$name = $value['submenu_state_title'];
				$m = 'target="_blank"';
				if ($value['type'] == 'url') {
					// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
					// $paths = $value['pdf_url'];
					$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
					$paths = 'javascript:void(0);';
				} elseif ($value['type'] == 'pdf') {
					$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
				}
				$html .= '<a class="btn" ' . $m . ' href="' . $paths . '">';
				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_state_base_level_one(`' . $menu_id . '`,`' . $title . '`,`' . $sub_title . '`,`' . $fmenu_id . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_state_base_level_one(`' . $menu_id . '`,`' . $title . '`,`' . $sub_title . '`,`' . $fmenu_id . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function get_category_base_submenu_details()
	{
		$level1_id = $this->input->post('level1_id');
		$level1_title = $this->input->post('level1_title');
		$query2 = $this->db->query("SELECT * FROM `tbl_submenu_details` WHERE  `submenu_id`='" . $level1_id . "' AND `status`='1' ORDER BY `category_name` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"></span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="';
				if ($num_rows == 2) {
					$html .= 'col-sm-6';
				} else {
					$html .= 'col-sm-3';
				}

				$html .= ' " style="font-size: 13px;">';
				$html .= '<a class="btn" onclick="get_category_base_level_one(' . $value['id'] . ',' . $level1_id . ',`' . $value['category_name'] . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				$html .= '<span id="open_now_four" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $value['category_name'] . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr>';
			$result['data'] = $html;
		} else if ($num_rows > 1) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"></span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				//$html .='<div class="col-sm-3" style="font-size: 13px;">';
				$html .= '<div class="';
				if ($num_rows == 2) {
					$html .= 'col-sm-6';
				} else {
					$html .= 'col-sm-3';
				}
				$html .= ' " style="font-size: 13px;">';
				$html .= '<a class="btn" onclick="get_category_base_level_one(' . $value['id'] . ',' . $level1_id . ',`' . $value['category_name'] . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $value['category_name'] . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr>';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr>';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function get_category_base_level_one()
	{
		$level2_id = $this->input->post('level2_id');
		$level1_id = $this->input->post('level1_id');
		$level2_title = $this->input->post('level2_title');
		$level1_title = $this->input->post('level1_title');
		$query2 = $this->db->query("SELECT * FROM `tbl_menu_level_one` WHERE  `sub_menu_category_id`='" . $level2_id . "' AND `status`='1' ORDER BY `submenu_state_title` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $level1_title . ' >> ' . $level2_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3 btn" style="font-size: 13px;">';
				//	1=>submenu,2=>state,3=>url&pdf	
				if ($value['sub_menu_ctegory_type'] == 1) {
					$name = $value['submenu_state_title'];
					$html .= '<a class="btn" onclick="get_category_base_level_two(' . $value['id'] . ',`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2) {
					$row = $this->db->get_where('master_states', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_two(' . $value['id'] . ',`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 3) {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn" ' . $m . ' href="' . $paths . '">';
				}

				$html .= '<span id="open_now_five" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_category_base_model(`' . $level1_id . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else if ($num_rows > 1) {

			foreach ($data as $key => $value) {
				if ($value['sub_menu_ctegory_type'] == 2) {
					$query2 = $this->db->query("SELECT tbl_menu_level_one.`pdf_url`, tbl_menu_level_one.`type`, tbl_menu_level_one.`sub_menu_ctegory_type`,tbl_menu_level_one.`id`,tbl_menu_level_one.`submenu_state_title` FROM `tbl_menu_level_one` INNER JOIN master_states ON tbl_menu_level_one.`submenu_state_title` = master_states.`id` WHERE tbl_menu_level_one.`sub_menu_category_id`='" . $level2_id . "' AND tbl_menu_level_one.`status`='1' ORDER BY master_states.`name` ASC");
				}
			}
			$data = $query2->result_array();

			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $level1_title . ' >> ' . $level2_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3 btn" style="font-size: 13px;">';
				//	1=>submenu,2=>state,3=>url&pdf	
				if ($value['sub_menu_ctegory_type'] == 1) {
					$name = $value['submenu_state_title'];
					$html .= '<a class="btn" onclick="get_category_base_level_two(' . $value['id'] . ',`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2) {
					$row = $this->db->get_where('master_states', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_two(' . $value['id'] . ',`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 3) {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn"  ' . $m . ' href="' . $paths . '">';
				}

				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_category_base_model(`' . $level1_id . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();show_category_base_model(`' . $level1_id . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function get_category_base_level_two()
	{
		$level3_id = $this->input->post('level3_id');
		$level2_id = $this->input->post('level2_id');
		$level1_id = $this->input->post('level1_id');
		$level3_title = $this->input->post('level3_title');
		$level2_title = $this->input->post('level2_title');
		$level1_title = $this->input->post('level1_title');
		$query2 = $this->db->query("SELECT * FROM `tbl_menu_level_two` WHERE  `sub_menu_category_id`='" . $level3_id . "' AND `status`='1' ORDER BY `submenu_state_title` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {

			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $level1_title . ' >> ' . $level2_title . ' >> ' . $level3_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3 btn" style="font-size: 13px;">';
				//	1=>submenu,2=>state,3=>url&pdf	
				if ($value['sub_menu_ctegory_type'] == 1) {
					$name = $value['submenu_state_title'];
					$html .= '<a  class="btn" onclick="get_category_base_level_three(' . $value['id'] . ',`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'state') {
					$row = $this->db->get_where('master_states', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_three(' . $value['id'] . ',`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'distric') {
					$row = $this->db->get_where('master_district', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_three(' . $value['id'] . ',`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 3) {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a  class="btn" ' . $m . '  href="' . $paths . '">';
				}

				$html .= '<span id="open_now_six" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_category_base_level_one(`' . $level2_id . '`,`' . $level1_id . '`,`' . $level2_title . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else if ($num_rows > 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $level1_title . ' >> ' . $level2_title . ' >> ' . $level3_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3 btn" style="font-size: 13px;">';
				//	1=>submenu,2=>state,3=>url&pdf	
				if ($value['sub_menu_ctegory_type'] == 1) {
					$name = $value['submenu_state_title'];
					$html .= '<a class="btn" onclick="get_category_base_level_three(' . $value['id'] . ',`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'state') {
					$row = $this->db->get_where('master_states', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_three(' . $value['id'] . ',`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'distric') {
					$row = $this->db->get_where('master_district', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_three(' . $value['id'] . ',`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 3) {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn" ' . $m . '  href="' . $paths . '">';
				}

				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_category_base_level_one(`' . $level2_id . '`,`' . $level1_id . '`,`' . $level2_title . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_category_base_level_one(`' . $level2_id . '`,`' . $level1_id . '`,`' . $level2_title . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function get_category_base_level_three()
	{
		$level4_id = $this->input->post('level4_id');
		$level3_id = $this->input->post('level3_id');
		$level2_id = $this->input->post('level2_id');
		$level1_id = $this->input->post('level1_id');
		$level4_title = $this->input->post('level4_title');
		$level3_title = $this->input->post('level3_title');
		$level2_title = $this->input->post('level2_title');
		$level1_title = $this->input->post('level1_title');
		$query2 = $this->db->query("SELECT * FROM `tbl_menu_level_three` WHERE  `sub_menu_category_id`='" . $level4_id . "' AND `status`='1' ORDER BY `submenu_state_title` ASC");
		$num_rows = $query2->num_rows();
		$data = $query2->result_array();

		$search_data = '<div class="col-md-2">&nbsp;</div><div class="col-md-2"><label>Search </label></div><div class="col-md-4 col-xs-10 d-flex p0">
                     <input ype="text" class="form-control mng" data-list="ssak_data_list" onkeyup="filters_data_state_base(this.value);" placeholder="Search....">
                     </div><div class="col-md-1 col-xs-2 p0">
                     <button style="float: left;" class="btn btn-outline-secondary btn-padd bor-right-r" type="button">
                     <i class="fa fa-search red-colr fs-16"> </i></button></div>';
		if ($num_rows == 1) {
			$result['action'] = 'open';
			$result['status'] = 1;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $level1_title . ' >> ' . $level2_title . ' >> ' . $level3_title . ' >> ' . $level3_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3 btn" style="font-size: 13px;">';
				//	1=>submenu,2=>state,3=>url&pdf	
				if ($value['sub_menu_ctegory_type'] == 1) {
					$name = $value['submenu_state_title'];
					$html .= '<a class="btn" onclick="get_category_base_level_four(' . $value['id'] . ',`' . $level4_id . '`,`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level4_title . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'state') {
					$row = $this->db->get_where('master_states', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_four(' . $value['id'] . ',`' . $level4_id . '`,`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level4_title . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'distric') {
					$row = $this->db->get_where('master_district', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_four(' . $value['id'] . ',`' . $level4_id . '`,`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level4_title . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 3) {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn" ' . $m . ' href="' . $paths . '">';
				}

				$html .= '<span id="open_now_seven" style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_category_base_level_two(`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else if ($num_rows > 1) {
			$result['action'] = 'list';
			$result['status'] = 2;
			$html = '<div class="row" style="margin: 1px;"><div class="col-sm-12"><span style="color:#484848;font-size: 11px;"> >> ' . $level1_title . ' >> ' . $level2_title . ' >> ' . $level3_title . ' >> ' . $level3_title . '</span></div></div>';
			$html .= '<div class="row text-center" style="padding: 10px;">';
			foreach ($data as $value) {
				$html .= '<div class="col-sm-3 btn" style="font-size: 13px;">';
				//	1=>submenu,2=>state,3=>url&pdf	
				if ($value['sub_menu_ctegory_type'] == 1) {
					$name = $value['submenu_state_title'];
					$html .= '<a class="btn" onclick="get_category_base_level_four(' . $value['id'] . ',`' . $level4_id . '`,`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level4_title . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'state') {
					$row = $this->db->get_where('master_states', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_four(' . $value['id'] . ',`' . $level4_id . '`,`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level4_title . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 2 && $value['type'] == 'distric') {
					$row = $this->db->get_where('master_district', array('id' => $value['submenu_state_title']))->row();
					$name = $row->name;
					$html .= '<a class="btn" onclick="get_category_base_level_four(' . $value['id'] . ',`' . $level4_id . '`,`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $name . '`,`' . $level4_title . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" href="javascript:void(0);">';
				}
				if ($value['sub_menu_ctegory_type'] == 3) {
					$name = $value['submenu_state_title'];
					$m = 'target="_blank"';
					if ($value['type'] == 'url') {
						// $m='onclick="(function(){alert(`Dear user you are now navigating to a third party url. Data shown on the next page is added and controlled by the third party`)})();"';
						// $paths = $value['pdf_url'];
						$m = 'onclick="alert_notification_mng(`' . $value['pdf_url'] . '`);"';
						$paths = 'javascript:void(0);';
					} elseif ($value['type'] == 'pdf') {
						$paths = base_url() . 'assets/uploads/menu_level_one/' . $value['pdf_url'];
					}
					$html .= '<a class="btn"  ' . $m . ' href="' . $paths . '">';
				}

				$html .= '<span style="color: #154EBF; cursor: pointer; font-size: 13px;">' . $name . '</span>';
				$html .= '</a>';
				$html .= '</div>';
			}
			$html .= '</div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_category_base_level_two(`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
			$result['data'] = $html;
		} else {
			$result['action'] = 'no record';
			$result['status'] = 3;
			$result['data'] = '<div class="col-md-12" style="margin: -8px 0 0 0; text-align: center;"><p style="color:black;">No Data is published by the State Government</p></div><hr><div class="text-center" style="padding-top:20px;"><button type="button" onclick="removevalue();get_category_base_level_two(`' . $level3_id . '`,`' . $level2_id . '`,`' . $level1_id . '`,`' . $level3_title . '`,`' . $level2_title . '`,`' . $level1_title . '`);" class="btn btn-primary backbutton" style="height: 31px;width: 120px;border-radius: 5%;font-size: 13px;"> 
                 <span class="⇠"></span> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back
              </button> 
            </div> ';
		}
		$result['search_data'] = $search_data;
		echo json_encode($result);
	}

	public function check_link_status()
	{
		$query2 = $this->db->query("SELECT submenu_state_title,pdf_url FROM `tbl_menu_level_one` WHERE `type`='url' AND `status`=1");
		$data = $query2->result();
		$status = '';
		foreach ($data as $datas) {
			$url = $datas->pdf_url;
			$headers = @get_headers($url);
			// Use condition to check the existence of URL 
			if ($headers && strpos($headers[0], '200')) {
			} else {
				$status .= "Doesn't Exist Title :  " . $datas->submenu_state_title . ' And url : ' . $url . '<br>';
			}
		}
		$query2 = $this->db->query("SELECT submenu_state_title,pdf_url FROM `tbl_menu_level_two` WHERE `type`='url' AND `status`=1");
		$data = $query2->result();
		foreach ($data as $datas) {
			$url = $datas->pdf_url;
			$headers = @get_headers($url);
			// Use condition to check the existence of URL 
			if ($headers && strpos($headers[0], '200')) {
			} else {
				$status .= "Doesn't Exist Title : " . $datas->submenu_state_title . ' And url : ' . $url . '<br>';
			}
		}
		$query2 = $this->db->query("SELECT submenu_state_title,pdf_url FROM `tbl_menu_level_three` WHERE `type`='url' AND `status`=1");
		$data = $query2->result();
		foreach ($data as $datas) {
			$url = $datas->pdf_url;
			$headers = @get_headers($url);
			// Use condition to check the existence of URL 
			if ($headers && strpos($headers[0], '200')) {
			} else {
				$status .= "Doesn't Exist Title : " . $datas->submenu_state_title . ' And url : ' . $url . '<br>';
			}
		}
		if (empty($status)) {
			$status = 'Congratulations All link working!';
		}
		$get_mail = $this->customerM->getOTPMail('1');
		$sign = 'Hello Sir,<br> This link are not working today <br>' . $status;
		$sub = 'Check Link Status';
		$email = 'singhm628@gmail.com';
		$send_to = $get_mail['send_to'];
		$this->email->from($send_to, 'VIS');
		$this->email->to($email);
		$this->email->subject($sub);
		$this->email->message($sign);
		$this->email->send();
		// echo $this->email->print_debugger();
		return 1;
	}
}
