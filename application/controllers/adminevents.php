<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Adminevents extends CI_Controller {

	var $language_id = '1';
	var $template = 'template/admin_event';
	var $template_merchant = 'merchant/';
	var $folder = 'admin_event';
	var $admin_url = 'adminevents';

	public function __construct() {
		parent::__construct();
		ini_set('memory_limit', '-1');

		$this -> load -> model('general_model', 'general');
		if (!$this -> session -> userdata('sess_language_id')) {
			$this -> session -> set_userdata('sess_language_id', '1');
		}
		$logged_in = $this -> session -> userdata('event_admin_logged_in');
		if ($logged_in === FALSE) {

			if (!isset($_SERVER['PHP_AUTH_USER'])) {
				header('WWW-Authenticate: Basic realm="Datetix Event Admin Panel"');
				header('HTTP/1.0 401 Unauthorized');
				echo("Please enter a valid username and password");
				exit();
			}

			$this -> general -> set_table('admin_event');
			$CheckAdminCondition['login'] = $_SERVER['PHP_AUTH_USER'];
			$CheckAdminCondition['password'] = $_SERVER['PHP_AUTH_PW'];
			$CheckAdmin = $this -> general -> get('', $CheckAdminCondition);

			if (!empty($CheckAdmin)) {
				$this -> session -> set_userdata('event_admin_logged_in', $CheckAdmin[0]);
			} else {
				header('WWW-Authenticate: Basic realm="Datetix Event Admin Panel"');
				header('HTTP/1.0 401 Unauthorized');
				echo("Please enter a valid username and password");
				exit();
			}
		}
		$this -> language_id = $this -> session -> userdata('sess_language_id');
			
		if ($language_id = $this -> input -> get('language_id')) {
			$this -> language_id = $language_id;
		}
		else {
			if ($city_id = $this -> input -> get('city_id')) {
				//$this -> language_id = $this -> getLangIdBycityID($city_id);
			}	
		}
	}

	public function index() {
		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		
		$data['languages'] = $this->_getLanguages();
		
		$data['cities'] = $this -> _getAdminCities();
		$data['seleccted_city_id'] = $data['cities'] ? $data['cities']['0']['city_id'] : '';
		
		if ($city_id = $this -> input -> get('city_id')) {
			$data['seleccted_city_id'] = $city_id;
			//$this -> language_id = $this -> getLangIdBycityID($city_id);
		}
		//Get Event datas
		$fields = array('e.event_id, e.event_start_time, e.event_end_time, e.price_door, e.tickets_sold_at_door, e.cash_collected_at_door, e.price_online, e.price_online_discounted', 'ct.city_id, ct.description as city_name', '(SELECT SUM(event_order.num_tickets) from event_order WHERE event_id = e.event_id) as online_prepaid_tkt', '(SELECT SUM(event_order.order_amount) from event_order WHERE event_id = e.event_id) as online_prepaid_amt', '(SELECT COUNT(DISTINCT user_ip) FROM log_event_ad WHERE event_id = e.event_id) as visitors', 'crncy.currency_id', 'crncy.description as currency_description', );
		$from = 'event as e';
		$joins = array('venue as v' => array('e.venue_id = v.venue_id', 'inner'), 'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'), 'city as ct' => array('ct.city_id = n.city_id', 'inner'), 'province as p' => array('p.province_id = ct.province_id', 'inner'), 'country as cntry' => array('p.country_id = cntry.country_id', 'LEFT'), 'currency as crncy' => array('cntry.currency_id = crncy.currency_id ', 'LEFT'));
		if ($city_id = $this -> input -> get('city_id')) {
			$city_ids = $city_id;
		} elseif ($data['seleccted_city_id']) {
			$city_ids = $data['seleccted_city_id'];
		}

		if ($city_ids) {
			$this -> db -> where_in('ct.city_id', explode(',', $city_ids));
		}
		//$this -> language_id = 3;

		//$where['el.display_language_id'] = $this -> language_id;
		$where['ct.display_language_id'] = $this -> language_id;
		$where['p.display_language_id'] = $this -> language_id;
		$where['n.display_language_id'] = $this -> language_id;
		$where['cntry.display_language_id'] = $this -> language_id;
		$where['crncy.display_language_id'] = $this -> language_id;

		$data['events'] = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'e.event_start_time desc', 'array', 'e.event_id');
		//echo $this->db->last_query();
		//echo "<pre>";print_r($data['events']);exit;

		$data['page_title'] = translate_phrase('Event Admin');
		$data['page_name'] = 'dashboard';
		$this -> load -> view($this -> template, $data);
	}
	
	public function logout() {
		$this -> session -> sess_destroy();
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);

		redirect('/');
	}

	/* Functino create event with details, Partners info, ticket list, and event photos
	 * @Params : $CurrentTab
	 * @Author : Rajnish
	 */
	public function create($event_id = "") {

		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		//$city_ids = '260,261,263';
		$this -> general -> set_table('event');
		$data['page_title'] = translate_phrase('Create Event');
		$event_venue_id = 0;
		if ($event_id) {
			//Get Event datas
			$fields = array('e.*');
			$from = 'event as e';
			$joins = array(
			//'event_language as el' => array('el.event_id = e.event_id', 'left'),
			'venue as v' => array('e.venue_id = v.venue_id', 'inner'), 'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'), 'city as ct' => array('ct.city_id = n.city_id', 'inner'), 'province as p' => array('p.province_id = ct.province_id', 'inner'), );
			if ($city_ids) {
				$this -> db -> where_in('ct.city_id', explode(',', $city_ids));
			}
			//$where['el.display_language_id'] = $this -> language_id;
			$where['ct.display_language_id'] = $this -> language_id;
			$where['p.display_language_id'] = $this -> language_id;
			$where['n.display_language_id'] = $this -> language_id;
			$where['e.event_id'] = $event_id;

			if ($event_data = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'e.event_start_time desc', 'array', 'e.event_id')) {
				$data = $event_data['0'];
				$event_venue_id = $data['venue_id'];

				if ($data['event_end_time'] < SQL_DATETIME) {
					$data['disabled_tab_msg'] = translate_phrase('You can not modify event details.');
					$data['is_past_event'] = "1";
					$data['page_title'] = translate_phrase('View Event');
				} else {
					$data['page_title'] = translate_phrase('Edit Event');
				}
				//$data['page_title'] .= ' - '.$data['event_name'];
			}
			unset($where);
		}

		if ($postData = $this -> input -> post()) {
			$event_language_data = $postData['event_language'];
			unset($postData['event_language']);

			$venue_city_id = $postData['venue_city_id'];
			//Set Event Date and time in SQL Formate
			if (isset($postData['event_start_time'])) {
				$postData['event_start_time'] = date('Y-m-d H:i:s', strtotime($postData['event_start_date'] . ' ' . $postData['event_start_time']));
				$postData['event_end_time'] = date('Y-m-d H:i:s', strtotime($postData['event_end_date'] . ' ' . $postData['event_end_time']));
				unset($postData['event_start_date']);
				unset($postData['event_end_date']);
			}

			//
			$postData['is_active'] = 1;

			unset($postData['venue_city_id']);
			$is_updated = 0;

			if ($postData['event_id'] && $postData['event_id'] > 0) {
				$event_id = $postData['event_id'];
				unset($postData['event_id']);
				$condition['event_id'] = $event_id;
				$this -> general -> update($postData, $condition);
				$is_updated = 1;
			} else {
				if (isset($postData['event_start_time'])) {
					$event_id = $this -> general -> simple_save($postData);
				} else {
					$event_id = 0;
				}
			}
			if ($event_id) {

				if (is_array($event_language_data['display_language_id'])) {
					if (isset($_FILES['flyer_url']['name'])) {
						$posterPath = 'event_flyers';
						if (!file_exists($posterPath . '/' . $event_id)) {
							mkdir($posterPath . '/' . $event_id, 0777, true);
						}

						for ($i = 0; $i < count($_FILES['flyer_url']['name']); $i++) {
							$config['upload_path'] = './event_flyers/' . $event_id;
							$config['allowed_types'] = 'gif|jpg|jpeg|png';
							$config['max_size'] = '0';
							$this -> load -> library('upload', $config);
							if ($this -> upload -> do_multiupload('flyer_url', $i)) {
								$file_upload_data = $this -> upload -> data();
								$flyer_imgs[$i] = $file_upload_data['file_name'];
							}
						}
					}
					$this -> general -> set_table('event_language');

					for ($i = 0; $i < count($event_language_data['display_language_id']); $i++) {
						$event_lang_condition['display_language_id'] = $event_language_data['display_language_id'][$i];
						$event_lang_condition['event_id'] = $event_id;

						$tmp['description'] = $event_language_data['description'][$i];
						$tmp['event_name'] = $event_language_data['event_name'][$i];

						if (isset($flyer_imgs[$i]) && $flyer_imgs[$i])
							$tmp['flyer_url'] = base_url('event_flyers/' . $event_id . '/' . $flyer_imgs[$i]);

						if ($event_language_db_data = $this -> general -> checkDuplicate($event_lang_condition)) {
							$this -> general -> update($tmp, $event_lang_condition);
						} else {
							$tmp = array_merge($tmp, $event_lang_condition);
							$event_id = $this -> general -> simple_save($tmp);
						}
						unset($tmp);
						unset($event_lang_condition);
					}
				}

				if ($is_updated) {
					$this -> session -> set_flashdata('success_msg', translate_phrase('Event updated successfully.'));
				} else {
					$this -> session -> set_flashdata('success_msg', translate_phrase('Event created successfully.'));
				}
				//$this->admin_url.'/create/'.$event_id
				redirect($this -> admin_url . '?city_id=' . $venue_city_id);
			}
		}
		$data['city_id'] = 0;
		//Load Event Form Data : Venues & Cities
		if ($city_id = $this -> input -> get('city_id')) {

			//change city_id while adding new event
			if (!isset($data['event_id'])) {
				$data['city_id'] = $city_id;
			}
		}
		$data['display_language_id'] = $this -> getLangIdBycityID($data['city_id']);
		$data['language_id'] = $data['display_language_id'];

		$eventTabData = $this -> _getEventTabData($event_venue_id, $data['city_id']);
		$data = array_merge($data, $eventTabData);

		if (isset($data['event_id'])) {
			$this -> general -> set_table('event_language');
			$event_lang_condition['event_id'] = $data['event_id'];
			if ($data['event_languages'] = $this -> general -> get("", $event_lang_condition, array('display_language_id' => 'asc'))) {
				$data['page_title'] .= ' - ' . $data['event_languages']['0']['event_name'];
			}
		}

		$event_city_id = isset($data['city_id']) ? $data['city_id'] : 0;
		$data['seleccted_city_id'] = $event_city_id;

		//Load Partner Tab Data : Venues & Cities
		$eventPartnerData = $this -> _getPartnerTabData($event_id, $event_city_id);
		$data = array_merge($data, $eventPartnerData);

		//Load Prepaid List Data
		$eventPrepaidListData = $this -> _getPrepaidListTabData($event_id);
		$data = array_merge($data, $eventPrepaidListData);

		//Load Event Photos
		$eventPhotoData = $this -> _getPhotoTabData($event_id);
		$data = array_merge($data, $eventPhotoData);

		$data['page_name'] = 'manage_event_details';

		//echo "<pre>";print_r($data);exit;
		$this -> load -> view($this -> template, $data);
	}

	private function getLangIdBycityID($city_id) {
		$fields = array('lang.display_language_id', 'lang.view_order', 'c.city_id', 'c.province_id');
		$from = 'country_display_language as lang';
		$joins = array('province as p' => array('p.country_id = lang.country_id ', 'LEFT'), 'city as c' => array('c.province_id = p.province_id', 'LEFT'), );

		$where['p.display_language_id'] = $this -> language_id;
		$data['default_display_language_id'] = "";
		$where['c.city_id'] = $city_id;
		$where['c.display_language_id'] = $this -> language_id;

		if ($lang_data = $this -> general -> multijoins_arr($fields, $from, $joins, $where, NULL, 'lang.view_order asc', 1)) {
			return $lang_data['0']['display_language_id'];
		} else {
			return 0;
		}
	}
	
	private function getLangBarById($city_id) {
		$fields = array('lang.display_language_id', 'lang.description');
		$from = 'display_language as lang';
		$joins = array(
					'country_display_language as cdl' => array('cdl.display_language_id = lang.display_language_id', 'LEFT'), 
					'province as p' => array('p.country_id = cdl.country_id ', 'LEFT'), 
					'city as c' => array('c.province_id = p.province_id', 'LEFT')
				);
		unset($where);
		$where['cdl.display_language_id'] = $this -> language_id;
		$where['p.display_language_id'] = $this -> language_id;
		$where['c.city_id'] = $city_id;
		$where['c.display_language_id'] = $this -> language_id;
		
		if ($lang_data = $this -> general -> multijoins_arr($fields, $from, $joins, $where, NULL, 'lang.view_order asc')) {
			
			$languages_datas = array();
			foreach ($lang_data as $lang) {
				$languages_datas[$lang['display_language_id']] = $lang['description'];
			}			
			return $languages_datas;
		} else {
			return array();
		}
		unset($where);
	}

	public function upload_event_photos($event_id) {
		$this -> load -> library('upload');

		$event_photo_path = 'event_photos/' . $event_id;
		if (!file_exists($event_photo_path)) {
			mkdir($event_photo_path, 0777, true);
		}

		$event_photo_thumb_path = $event_photo_path . '/thumbs';
		if (!file_exists($event_photo_thumb_path)) {
			mkdir($event_photo_thumb_path, 0777, true);
		}

		$config['upload_path'] = './' . $event_photo_path . '/';
		$config['allowed_types'] = 'gif|jpg|jpeg|png';
		$config['max_size'] = '0';
		$this -> upload -> initialize($config);

		if ($this -> upload -> do_upload('fileToUpload')) {
			$image_data = $this -> upload -> data();

			$data['image'] = $image_data['file_name'];

			$thumb_config = array('source_image' => $image_data['full_path'], //get original image
			'new_image' => $event_photo_thumb_path, //save as new image //need to create thumbs first
			//'maintain_ratio' => true,
			'width' => 200, 'height' => 200);
			$this -> load -> library('image_lib', $thumb_config);
			//load library
			$this -> image_lib -> resize();
			//generating thumb

			$saveData['event_id'] = $event_id;
			$saveData['photo'] = $data['image'];

			$saveData['view_order'] = '`view_order+1`';

			$saveData['event_admin_id'] = $city_ids = $this -> session -> userdata['event_admin_logged_in']['admin_event_id'];

			$this -> general -> set_table('event_photos');
			$data['event_photo_id'] = $this -> general -> save($saveData);

			$data['url'] = base_url('event_photos/' . $event_id . '/' . $data['image']);
			$data['thumb_url'] = base_url('event_photos/' . $event_id . '/thumbs/' . $data['image']);

			$data['success'] = 1;
		} else {
			$data['msg'] = $this -> upload -> display_errors('', '');
			$data['success'] = 0;
		}
		echo json_encode($data);
	}

	public function updateEventPhotoViewOrder($event_id) {
		$event_photos = $this -> input -> post('data');
		$this -> general -> set_table('event_photos');
		if ($event_photos) {
			foreach ($event_photos as $img_data) {
				$data['view_order'] = $img_data['view_order'];
				$condition['event_photo_id'] = $img_data['id'];
				$condition['event_id'] = $event_id;
				$condition['event_admin_id'] = $this -> session -> userdata['event_admin_logged_in']['admin_event_id'];
				$this -> general -> update($data, $condition);
			}
		}
	}

	public function deleteEventPhoto($event_id) {
		if ($event_id) {
			$this -> general -> set_table('event_photos');
			$condition['event_photo_id'] = $this -> input -> post('id');
			$condition['event_id'] = $event_id;
			$condition['event_admin_id'] = $this -> session -> userdata['event_admin_logged_in']['admin_event_id'];
			if ($imgData = $this -> general -> get("", $condition)) {
				$imgName = $imgData['0']['photo'];

				$filename = './event_photos/' . $event_id . '/' . $imgName;
				$thumb_filename = './event_photos/' . $event_id . '/thumbs/' . $imgName;

				if (file_exists($filename)) {
					unlink($filename);
				}
				if (file_exists($thumb_filename)) {
					unlink($thumb_filename);
				}
				$this -> general -> delete($condition);
			}

			$data['success'] = 1;
		} else {
			$data['success'] = 0;
		}
		echo json_encode($data);

	}

	private function _getPhotoTabData($event_id) {

		$this -> general -> set_table('event_photos');

		$condition['event_id'] = $event_id;
		$condition['event_admin_id'] = $this -> session -> userdata['event_admin_logged_in']['admin_event_id'];
		$order_by['view_order'] = 'asc';
		$this -> db -> order_by('view_order=0, view_order');
		$data['event_photos'] = $this -> general -> get("", $condition);
		return $data;
	}

	private function _getAdminCities() {
		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];

		//Current Lived in
		$fields = array('c.city_id, c.description', 'crncy.currency_id', 'crncy.description as currency_description', );
		$from = 'city as c';
		$joins = array('province as prvnce' => array('c.province_id = prvnce.province_id', 'LEFT'), 'country as cntry' => array('prvnce.country_id = cntry.country_id', 'LEFT'), 'currency as crncy' => array('cntry.currency_id = crncy.currency_id ', 'LEFT'));
		$this -> db -> where_in('c.city_id', explode(',', $city_ids));
		$where['c.display_language_id'] = $this -> language_id;
		$where['cntry.display_language_id'] = $this -> language_id;
		$where['crncy.display_language_id'] = $this -> language_id;
		return $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'c.view_order asc', 'array', 'c.city_id');

	}

	private function _getEventTabData($event_venue_id, $city_id) {
		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		$data['cities'] = $this -> _getAdminCities();

		if ($event_venue_id) {
			//Current Lived in
			$fields = array('c.city_id');
			$from = 'venue as v';
			$joins = array('neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'INNER'), 'city as c' => array('c.city_id = n.city_id', 'LEFT'));
			$where['c.display_language_id'] = $this -> language_id;
			$where['n.display_language_id'] = $this -> language_id;
			$where['v.venue_id'] = $event_venue_id;

			if ($event_venue = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'v.name asc', 'array', 'v.venue_id')) {
				$city_ids = $event_venue['0']['city_id'];
				$data['city_id'] = $city_ids;
			}
			unset($where);
		} else {
			if ($city_id) {
				$data['city_id'] = $city_id;
			} else {
				$data['city_id'] = $data['cities'] ? $data['cities']['0']['city_id'] : '';
			}
		}
		//Current Lived in
		$fields = array('v.venue_id', 'v.name');
		$from = 'venue as v';
		$joins = array('neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'INNER'), 'city as c' => array('c.city_id = n.city_id', 'LEFT'));

		if (isset($data['city_id']) && $data['city_id'])
			$this -> db -> where_in('c.city_id', $data['city_id']);
		else
			$this -> db -> where_in('c.city_id', explode(',', $city_ids));

		$where['c.display_language_id'] = $this -> language_id;
		$data['venues'] = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'v.name asc', 'array', 'v.venue_id');

		return $data;
	}

	public function getVenuesByCityId($city_id = "") {
		//Current Lived in
		$fields = array('v.venue_id', 'v.name');
		$from = 'venue as v';
		$joins = array('neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'INNER'), 'city as c' => array('c.city_id = n.city_id', 'LEFT'), );
		$where['c.city_id'] = $city_id;
		$where['c.display_language_id'] = $this -> language_id;
		$data['venues'] = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'v.name asc', 'array', 'v.venue_id');

		$fields = array('lang.display_language_id', 'lang.view_order', 'c.city_id', 'c.province_id');
		$from = 'country_display_language as lang';
		$joins = array('province as p' => array('p.country_id = lang.country_id ', 'LEFT'), 'city as c' => array('c.province_id = p.province_id', 'LEFT'), );
		$where['p.display_language_id'] = $this -> language_id;
		$data['default_display_language_id'] = "";
		if ($lang_data = $this -> general -> multijoins_arr($fields, $from, $joins, $where, NULL, 'lang.view_order asc', 1)) {
			$data['default_display_language_id'] = $lang_data['0']['display_language_id'];
		}

		echo json_encode($data);
	}

	private function _getPrepaidListTabData($event_id = "") {
		$data = array();
		if ($event_id) {

			//Current Lived in
			$fields = array('event_order.*', 'crncy.description as currency_description', );
			$from = 'event_order';
			$joins = array('currency as crncy' => array('event_order.currency_id = crncy.currency_id ', 'LEFT'));

			$where['crncy.display_language_id'] = $this -> language_id;
			$where['event_order.event_id'] = $event_id;
			$data['event_orders'] = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'event_order.order_time desc', 'array');

			if ($data['event_orders']) {
				foreach ($data['event_orders'] as $key => $user_order) {
					$data['event_orders'][$key]['gender'] = '';
					$data['event_orders'][$key]['birth_date'] = '';

					if ($user_order['paid_by_user_id']) {
						$this -> general -> set_table('user');
						if ($user_info = $this -> general -> get("first_name,last_name,mobile_phone_number,gender_id,birth_date", array('user_id' => $user_order['paid_by_user_id']))) {
							$data['event_orders'][$key]['paid_by_name'] = $user_info['0']['first_name'] . ' ' . $user_info['0']['last_name'];
							$data['event_orders'][$key]['paid_by_mobile_phone_number'] = $user_info['0']['mobile_phone_number'];
							$data['event_orders'][$key]['birth_date'] = $user_info['0']['birth_date'];

							//@TODO : Write a static condition for male female - ingore db call for optimization..
							$data['event_orders'][$key]['gender'] = $user_info['0']['gender_id'] == 1 ? "M" : "F";
						}

						//If not email address then fetch email from user_email table.
						if ($data['event_orders'][$key]['paid_by_email']) {
							$this -> general -> set_table('user_email');
							if ($user_info = $this -> general -> get("email_address", array('user_id' => $user_order['paid_by_user_id']))) {
								$data['event_orders'][$key]['paid_by_email'] = $user_info['0']['email_address'];
							}
						}
					}
				}
			}
			//echo "<pre>"; print_r($data['event_orders']);exit;

		}
		return $data;
	}

	private function _getPartnerTabData($event_id, $event_city_id) {
		$this -> general -> set_table('event_url');
		$data['event_urls'] = $this -> general -> get("", array('event_id' => $event_id), array('event_url_id' => 'asc'));

		$data['languages'] = $this -> _getLanguages();
		//$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		if ($event_city_id) {
			$sql = "SELECT 
				partner.*,(SELECT COUNT(DISTINCT user_ip) FROM log_event_ad WHERE event_id='".$event_id."' and log_event_ad.ad_id = partner.partner_id) as unique_visitors FROM partner  
				WHERE city_id = $event_city_id ORDER BY name";
			$data['partners'] = $this -> general -> sql_query($sql);

		} else {
			$data['partners'] = array();
		}
		//echo "<pre>";print_r($data);exit;
		return $data;
	}

	public function save_event_partner() {
		if ($postData = $this -> input -> post()) {
			$event_id = $postData['event_id'];
			$event_urls = $postData['event_url'];

			//@TODO: Ads id is not inserted in event_url table :( [Need to discuss]
			$is_record_changed = false;
			if ($event_urls && $event_id) {

				$this -> general -> set_table('event_url');
				foreach ($event_urls as $event_data) {
					if (isset($event_data['event_url_id']) && $event_data['event_url_id']) {
						$event_url_id = $event_data['event_url_id'];
						unset($event_data['event_url_id']);
						$flag = $this -> general -> update($event_data, array('event_url_id' => $event_url_id));
						if ($flag)
							$is_record_changed = true;
					} else {
						$event_data['event_id'] = $event_id;
						$id = $this -> general -> save($event_data);
						if ($id)
							$is_record_changed = true;
					}
				}
			}

			if ($is_record_changed) {
				$this -> session -> set_flashdata('success_msg', translate_phrase('Event URL is saved successfully.'));
			} else {
				$this -> session -> set_flashdata('error_msg', translate_phrase('Error occured while saving db, Please try again.'));
			}
			redirect($this -> admin_url . '/create/' . $event_id . '#partner');
		}
	}
	
	public function save_signup_partner() {
		if ($postData = $this -> input -> post()) {
			
			$signup_urls = $postData['signup_url'];
			
			
			//@TODO: Ads id is not inserted in signup_url table :( [Need to discuss]
			$is_record_changed = false;
			if ($signup_urls) {

				$this -> general -> set_table('signup_url');
				foreach ($signup_urls as $event_data) {
					
					if (isset($event_data['signup_url_id']) && $event_data['signup_url_id']) {
						$signup_url_id = $event_data['signup_url_id'];
						unset($event_data['signup_url_id']);
						$flag = $this -> general -> update($event_data, array('signup_url_id' => $signup_url_id));
						if ($flag)
							$is_record_changed = true;
					} else {
						$id = $this -> general -> save($event_data);
						if ($id)
							$is_record_changed = true;
					}
				}
			}

			if ($is_record_changed) {
				$this -> session -> set_flashdata('success_msg', translate_phrase('Signup URL is saved successfully.'));
			} else {
				$this -> session -> set_flashdata('error_msg', translate_phrase('Error occured while saving db, Please try again.'));
			}
			redirect($this -> admin_url . '/singup_urls?city_id='.$this->input->get('city_id'));
		}
	}
	
	
	/* [Ajax-call] Delete Event Language */
	public function delete_signup_partner() {
		$response['type'] = 'error';
		$response['msg'] = translate_phrase('Error occured while deleting record Please try again');
		if ($signup_url_id = $this -> input -> post('signup_url_id')) {
			$this -> general -> set_table('signup_url');
			if ($this -> general -> delete(array('signup_url_id' => $signup_url_id))) {
				$response['type'] = 'success';
				$response['msg'] = translate_phrase('Record is deleted.');
			}
		}

		echo json_encode($response);
	}
	/* [Ajax-call] Delete Event Language */
	public function delete_event_language() {
		$response['type'] = 'error';
		$response['msg'] = translate_phrase('Error occured while deleting record Please try again');
		$event_id = $this -> input -> post('event_id');
		$display_language_id = $this -> input -> post('display_language_id');

		if ($event_id && $display_language_id) {
			$this -> general -> set_table('event_language');
			if ($this -> general -> delete(array('event_id' => $event_id, 'display_language_id' => $display_language_id))) {
				$response['type'] = 'success';
				$response['msg'] = translate_phrase('Record is deleted.');
			}
		}

		echo json_encode($response);
	}

	/* Delete Event Partner */
	public function delete_event_partner() {
		$response['type'] = 'error';
		$response['msg'] = translate_phrase('Error occured while deleting record Please try again');
		if ($event_url_id = $this -> input -> post('event_url_id')) {
			$this -> general -> set_table('event_url');
			if ($this -> general -> delete(array('event_url_id' => $event_url_id))) {
				$response['type'] = 'success';
				$response['msg'] = translate_phrase('Record is deleted.');
			}
		}

		echo json_encode($response);
	}

	public function create_venue($event_id = "") {
		if ($postData = $this -> input -> post()) {
			$this -> general -> set_table('venue');
			$venue_id = "0";
			for ($i = 0; $i < count($postData['language']); $i++) {
				$saveData['display_language_id'] = $postData['language'][$i];

				$saveData['name'] = $postData['name'][$i];
				$saveData['address'] = $postData['address'][$i];

				$saveData['neighborhood_id'] = $postData['neighborhood_id'];
				$saveData['phone_number'] = $postData['phone_number'];
				$saveData['website_url'] = $postData['website_url'];
				$saveData['review_url'] = null;
				if ($venue_id > 0) {
					$saveData['venue_id'] = $venue_id;
				}
				$venue_id = $this -> general -> simple_save($saveData);
			}
			$this -> session -> set_flashdata('success_msg', translate_phrase('Venue added successfully.'));
			redirect($this -> admin_url . '/create/' . $event_id);
		}

		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		$city_select = "select city.city_id, city.description from city WHERE display_language_id = $this->language_id AND city.city_id IN ($city_ids) ORDER BY city.description";
		$data['cities'] = $this -> general -> sql_query($city_select);

		$cities = array();

		$city_id = 0;
		foreach ($data['cities'] as $key => $city) {
			if ($key == 0)
				$city_id = $city['city_id'];

			$cities[$city['city_id']] = $city['description'];
		}

		if ($this -> input -> get('city_id')) {
			$city_id = $this -> input -> get('city_id');
		}

		$data['display_language_id'] = $this -> getLangIdBycityID($city_id);
		$data['selected_city_id'] = $city_id;
		$data['selected_neighborhood_id'] = $city_id;

		$neighbor_condition['city_id'] = $city_id;
		$neighbor_condition['display_language_id'] = $this -> language_id;

		$this -> general -> set_table('neighborhood');
		$data['neighborhoods'] = $this -> general -> get("neighborhood_id,description", $neighbor_condition, array('view_order' => 'asc'));

		if ($data['neighborhoods']) {
			$dropData = array();
			foreach ($data['neighborhoods'] as $value) {
				$dropData[$value['neighborhood_id']] = $value['description'];
			}
			$data['neighborhoods'] = $dropData;
		}
		$data['event_id'] = $event_id;
		$data['cities'] = $cities;
		$data['languages'] = $this -> _getLanguages();
		$data['page_title'] = translate_phrase('Create Venue');
		$data['page_name'] = 'add_new_venue';
		$this -> load -> view($this -> template, $data);
	}

	public function getNeighborhoodByCityId($city_id) {
		$neighbor_condition['city_id'] = $city_id;
		$neighbor_condition['display_language_id'] = $this -> language_id;

		$this -> general -> set_table('neighborhood');
		$data['neighborhoods'] = $this -> general -> get("neighborhood_id,description", $neighbor_condition, array('view_order' => 'asc'));

		$fields = array('lang.display_language_id', 'lang.view_order', 'c.city_id', 'c.province_id');
		$from = 'country_display_language as lang';
		$joins = array('province as p' => array('p.country_id = lang.country_id ', 'LEFT'), 'city as c' => array('c.province_id = p.province_id', 'LEFT'), );
		$where['p.display_language_id'] = $this -> language_id;
		$where['c.city_id'] = $city_id;
		$where['c.display_language_id'] = $this -> language_id;

		$data['default_display_language_id'] = "";
		if ($lang_data = $this -> general -> multijoins_arr($fields, $from, $joins, $where, NULL, 'lang.view_order asc', 1)) {
			$data['default_display_language_id'] = $lang_data['0']['display_language_id'];
		}

		echo json_encode($data);
	}

	public function add_new_partner($event_id = "") {

		if ($postData = $this -> input -> post()) {
			$this -> load -> library('form_validation');
			$this -> form_validation -> set_rules('default_event_url', translate_phrase('Default URL Shortcut'), 'trim|required|xss_clean|is_unique[partner.default_event_url]');
			$this -> form_validation -> set_rules('name', translate_phrase('Name'), 'trim|required');

			$this -> form_validation -> set_error_delimiters('', '');
			$this -> form_validation -> set_message('is_unique', translate_phrase('Default URL shortcut already used by another partner. Please enter a different default URL shortcut.'));
			
			if ($this -> form_validation -> run() == TRUE) {
				$event_id = $postData['event_id'];
				$this -> general -> set_table('partner');
				unset($postData['event_id']);
				
				
				$postData['default_event_url'] = trim($postData['default_event_url']);
				$postData['name'] = trim($postData['name']);
				$postData['login'] = trim($postData['login']);
				$postData['password'] = trim($postData['password']);
				
				//
				if($postData['login'] && !$postData['password'])
				{
					$data = $postData;
					$data['error_msg_password'] = translate_phrase('You must enter a password for this login');
				}
				else if($postData['login'] && $this->general->checkDuplicate(array('login'=>$postData['login'])))
				{
					$data = $postData;
					$data['error_msg'] = translate_phrase('This login has already been used. Please enter a different login.');
				}
				else {
					$postData['default_display_language_id'] = $postData['default_language_id'];
					unset($postData['default_language_id']);
					$this -> general -> save($postData);
					$this -> session -> set_flashdata('success_msg', translate_phrase('Event Partner added successfully.'));
					$city_id = "";
					
					if($this->input->get('return_to'))
					{
						$url = '/'.$this->input->get('return_to');
						if ($this -> input -> get('city_id')) {
							$city_id = $this -> input -> get('city_id');
							$url .= "?city_id=" . $city_id;
						}
					}
					else {
						if ($this -> input -> get('city_id')) {
							$city_id = $this -> input -> get('city_id');
							$event_id .= "?city_id=" . $city_id;
						}
						$url = '/create/' . $event_id . '#partner';
					}
					redirect($this -> admin_url . $url);
				}
			} else {
				$data = $postData;
			}
		}

		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		$city_select = "select city.city_id, city.description from city WHERE display_language_id = $this->language_id AND city.city_id IN ($city_ids) ORDER BY city.description";
		$data['cities'] = $this -> general -> sql_query($city_select);

		$cities = array();

		$city_id = 0;
		foreach ($data['cities'] as $key => $city) {
			if ($key == 0)
				$city_id = $city['city_id'];

			$cities[$city['city_id']] = $city['description'];
		}
		if ($this -> input -> get('city_id')) {
			$city_id = $this -> input -> get('city_id');
		}
		$data['selected_city_id'] = $city_id;
		$data['event_id'] = $event_id;

		$data['cities'] = $cities;
		$data['languages'] = $this -> _getLanguages();
		$data['page_title'] = translate_phrase('Create Partner');
		$data['page_name'] = 'add_new_partner';
		$this -> load -> view($this -> template, $data);
	}

	private function _getLanguages() {
		$this -> general -> set_table('display_language');
		$languages = $this -> general -> get("", array(), array('view_order' => 'asc'));

		$languages_datas = array();
		if ($languages) {
			foreach ($languages as $lang) {
				$languages_datas[$lang['display_language_id']] = $lang['description'];
			}
		}
		return $languages_datas;
	}

	public function uploadFlayer($event_id = "") {
		$this -> load -> library('upload');
		//First store photos in temp
		if ($event_id) {
			$posterPath = 'event_flyers';
			if (!file_exists($posterPath . '/' . $event_id)) {
				mkdir($posterPath . '/' . $event_id, 0777, true);
			}

			$config['upload_path'] = './event_flyers/' . $event_id;
		} else {
			$config['upload_path'] = './event_flyers/tmp/';
		}
		$config['allowed_types'] = 'gif|jpg|jpeg|png';
		$config['max_size'] = '0';
		$this -> upload -> initialize($config);
		if ($this -> upload -> do_upload('fileToUpload')) {
			$image_data = $this -> upload -> data();
			$data['image'] = $image_data['file_name'];

			if ($event_id) {
				$data['url'] = base_url('event_flyers/' . $event_id . '/' . $data['image']);

				$updateEventData['poster_url'] = $data['url'];
				$this -> general -> set_table('event');
				$this -> general -> update($updateEventData, array('event_id' => $event_id));

			} else {
				$this -> session -> set_userdata('flayer_img', $data['image']);
				$data['url'] = base_url('event_flyers/tmp/' . $data['image']);
			}
			$data['success'] = 1;
		} else {
			$data['msg'] = $this -> upload -> display_errors('', '');
			$data['success'] = 0;
		}

		echo json_encode($data);
	}
	
	/* Singup urls list */
	function singup_urls(){
		$admin_data = $this->session->userdata('event_admin_logged_in');
		$parnter_id = $admin_data['admin_event_id'];
		
		$this->general->set_table('signup_url');
		$singup_url_condition['partner_id'] = $parnter_id;
		$singup_url_condition = array();
		$data['singup_urls'] = $this -> general -> get("",$singup_url_condition,array('signup_url_id'=>'asc'));
		//print_r($data['singup_urls'] );exit;
		$data['languages'] = $this->_getLanguages();
		
		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];		
		
		$data['cities'] = $this -> _getAdminCities();
		$data['seleccted_city_id'] = $data['cities'] ? $data['cities']['0']['city_id'] : $city_ids;
		
		if ($city_id = $this -> input -> get('city_id')) {
			$data['seleccted_city_id'] = $city_id;
			//$this -> language_id = $this -> getLangIdBycityID($city_id);
		}
		if ($data['seleccted_city_id']) {
			$sql = "SELECT * FROM partner WHERE city_id = '".$data['seleccted_city_id']."' ORDER BY name";
			$data['partners'] = $this -> general -> sql_query($sql);

		} else {
			$data['partners'] = array();
		}
		$data['page_title'] = translate_phrase('Merchant List');
		$data['page_name'] = 'singup_urls';
		$this -> load -> view($this -> template, $data);
	
	}
	
	/* Merchant list */
	public function merchant_list() {
		
		
		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];		
		
		$data['cities'] = $this -> _getAdminCities();
		$data['seleccted_city_id'] = $data['cities'] ? $data['cities']['0']['city_id'] : $city_ids;
		
		if ($city_id = $this -> input -> get('city_id')) {
			$data['seleccted_city_id'] = $city_id;
			//$this -> language_id = $this -> getLangIdBycityID($city_id);
		}
		
		$this -> db -> where_in('city_id', explode(',', $data['seleccted_city_id']));
		$neighbor_condition['display_language_id'] = $this -> language_id;
		$this -> general -> set_table('neighborhood');

		$data['neighborhoods'] = $this -> general -> get("neighborhood_id,description", $neighbor_condition, array('view_order' => 'asc'));

		//echo $this->db->last_query();
		//echo "<pre>";print_r($data['neighborhoods']);exit;
		$data['selected_neighborhood_id'] = 0;
		

		$merchant_condition = array();
		if ($neighborhood_id = $this -> input -> get('neighborhood_id')) {
			$data['selected_neighborhood_id'] = $neighborhood_id;
			$merchant_condition['neighborhood_id'] = $neighborhood_id;
		}

		$fields = array('merchant.*, n.description as neighborhood_description');
		$from = 'merchant';
		$joins = array('neighborhood as n' => array('merchant.neighborhood_id = n.neighborhood_id', 'LEFT'));
		$where['n.display_language_id'] = $this -> language_id;
		
		if($data['selected_neighborhood_id'])
			$where['merchant.neighborhood_id'] = $data['selected_neighborhood_id'];
		
		$order_by = "merchant.name asc, merchant.view_order asc";
		
		//Load user data
		if ($this -> input -> post('page_no')) {
			$page_no = $this -> input -> post('page_no');
		} else {
			$page_no = 1;
		}

		$data['page_no'] = $page_no;
		
		$limit = "100";
		$offset = ($page_no - 1) * $limit;
	
		$data['merchants'] = $this -> general -> multijoins_arr($fields, $from, $joins, $where, NULL, $order_by,$limit,$offset);
		/*$this->general->set_table('merchant');
		 $data['merchants'] = $this -> general -> get("",$merchant_condition,array('view_order'=>'asc'),10);
		 */
		$data['languages'] = $this->_getLanguages();
		
		$data['page_title'] = translate_phrase('Merchant List');
		if ($this -> input -> is_ajax_request()) {
			$this -> load -> view('admin_event/'.$this -> template_merchant . 'merchantlist_row', $data);	
		}
		else
		{
			$data['page_name'] = $this -> template_merchant . 'merchantlist';
			$this -> load -> view($this -> template, $data);
		}
		
	}

	/* Functino create event with details, Partners info, ticket list, and event photos
	 * @Params : $CurrentTab
	 * @Author : Rajnish
	 */
	public function create_merchant($merchant_id="") {
		
		$data['page_title'] = translate_phrase('Create Merchant');
		$selected_city_id = "";
		if ($city_id = $this -> input -> get('city_id')) {
			$selected_city_id = $city_id;
			
			//$this -> language_id = $this -> getLangIdBycityID($city_id);
		}
		
		if ($merchant_id) {
			//Get Merchant datas
			$fields = array('merchant.*, n.description as neighborhood_description');
			$from = 'merchant';
			$joins = array('neighborhood as n' => array('merchant.neighborhood_id = n.neighborhood_id', 'LEFT'));
			$where['n.display_language_id'] = $this -> language_id;
			$where['merchant.merchant_id'] = $merchant_id;

			if ($merchant_data = $this -> general -> multijoins_groupby($fields, $from, $joins, $where, 'merchant.view_order desc', 'array', 'merchant.merchant_id')) {
				$data = $merchant_data['0'];
				$data['page_title'] = translate_phrase('Edit Merchant');
				$data['page_title'] .= ' - '.$data['name'];
			}
			unset($where);
		}

		if ($postData = $this -> input -> post()) {
			$this -> general -> set_table('merchant');
			
			if ($postData['merchant_id'] && $postData['merchant_id'] > 0) {
				$merchant_id = $postData['merchant_id'];
				
				
				$merchant_cuisine = isset($postData['merchant_cuisine'])?$postData['merchant_cuisine']:array();
				$merchant_date_type = isset($postData['merchant_date_type'])?$postData['merchant_date_type']:array();
				
				unset($postData['merchant_id']);
				unset($postData['merchant_cuisine']);
				unset($postData['merchant_date_type']);
				
				$condition['merchant_id'] = $merchant_id;
				
				if(!isset($postData['is_featured']))
				{
					$postData['is_featured'] = 0;
				}
				$this -> general -> update($postData, $condition);
				
				
				if($merchant_cuisine)
				{
					$merchant_cuisine = explode(',', $merchant_cuisine);
					
					$this -> general -> set_table('merchant_cuisine');
					$tmp_save['merchant_id'] = $merchant_id;
					$this -> general -> delete($tmp_save);
					
					foreach($merchant_cuisine as $cuisine)
					{
						$tmp_save['cuisine_id'] = $cuisine;
						$tmp_save['uploaded_time'] = SQL_DATETIME;						
						$this -> general -> simple_save($tmp_save);
					}
				}
				if($merchant_date_type)
				{
					$merchant_date_type = explode(',', $merchant_date_type);
					
					$this -> general -> set_table('merchant_date_type');
					$tmp_save['merchant_id'] = $merchant_id;
					$this -> general -> delete($tmp_save);
					
					foreach($merchant_date_type as $value)
					{
						$tmp_save['date_type_id'] = $value;
						$this -> general -> simple_save($tmp_save);
					}
				}
				
			$is_updated = 1;
			} else {
				$merchant_id = $this -> general -> simple_save($postData);
			}
			
			if ($merchant_id ) {
				if ($is_updated) {
					$this -> session -> set_flashdata('success_msg', translate_phrase('Merchant updated successfully.'));
					//redirect($this -> admin_url . '/create_merchant/'.$merchant_id.'?neighborhood_id=' . $postData['neighborhood_id'].'&city_id'.$selected_city_id);
				} else {
					$this -> session -> set_flashdata('success_msg', translate_phrase('Merchant created successfully.'));
				}
				redirect($this -> admin_url . '/merchant_list?neighborhood_id=' . $postData['neighborhood_id'].'&city_id'.$selected_city_id);	
			}
		}
		if ($merchant_id ) {
							
			$condition['merchant_id'] = $merchant_id;
			$this -> general -> set_table('merchant_photo');
			$order_by['view_order'] = 'asc';
			$this -> db -> order_by('view_order=0, view_order');		
			$data['merchant_photos'] = $this -> general -> get("", $condition);
			
			$this -> general -> set_table('merchant_cuisine');
			$data['merchant_cuisine_list'] = $this -> general -> get("cuisine_id", array('merchant_id'=>$merchant_id));
			
			$this -> general -> set_table('merchant_date_type');
			$data['merchant_date_type'] = $this -> general -> get("date_type_id", array('merchant_id'=>$merchant_id));
			
			
			$this -> general -> set_table('date_type');
			$data['date_types'] = $this -> general -> get("date_type_id,description", array('display_language_id'=>$this->language_id),array('view_order'=>'asc'));
			//echo "<pre>";print_r($date_types);exit;
			
			$this -> general -> set_table('cuisine_category');
			$cuisine_list = $this -> general -> get("", array('display_language_id'=>$this->language_id),array('view_order'=>'asc'));
			
			if($cuisine_list)
			{
				$this -> general -> set_table('cuisine');
				foreach($cuisine_list as $key=>$value)
				{
					$tmp['display_language_id'] = $this->language_id;
					$tmp['cuisine_category_id'] = $value['cuisine_category_id'];					
					$cuisine_list[$key]['list'] = $this -> general -> get("",$tmp);
				}
			}	
			//echo "<pre>";print_r($date_types);exit;
			$data['cuisine_list'] = $cuisine_list;		
		}
		
		$city_ids = $this -> session -> userdata['event_admin_logged_in']['city_ids'];
		if($selected_city_id)
		{
			$city_ids = $selected_city_id;
		}
		
		$this -> db -> where_in('city_id', explode(',', $city_ids));
		$neighbor_condition['display_language_id'] = $this -> language_id;
		$this -> general -> set_table('neighborhood');
		$neighborhoods = $this -> general -> get("neighborhood_id,description", $neighbor_condition, array('view_order' => 'asc'));
		$neighborhood_dropdown_data = array();
		if($neighborhoods)
		{
			foreach($neighborhoods as $item)
			{
				$neighborhood_dropdown_data[$item['neighborhood_id']] = $item['description'];
			}
		}
		
		$selected_neighborhood_id = (isset($neighborhoods['0']) && $neighborhoods['0']) ? $neighborhoods['0']['neighborhood_id'] : '0';
		$merchant_condition = array();
		if ($neighborhood_id = $this -> input -> get('neighborhood_id')) {
			$selected_neighborhood_id = $neighborhood_id;
		}
		
		
		$data['selected_neighborhood_id'] = $selected_neighborhood_id;
		$data['neighborhood_dropdown_data'] = $neighborhood_dropdown_data;
		
		$this->general->set_table('budget');
		$budget_condition['display_language_id'] = $this -> language_id;
		$budget = $this -> general -> get("", $budget_condition, array('view_order' => 'asc'));
		$budget_dropdown_data = array();
		if($budget)
		{
			foreach($budget as $item)
			{
				$budget_dropdown_data[$item['budget_id']] = $item['description'];
			}
		}
		$data['budget_dropdown_data'] = $budget_dropdown_data;
		$data['page_name'] = 'merchant/manage_merchant_details';

		//echo "<pre>";print_r($data);exit;
		$this -> load -> view($this -> template, $data);
	}
	

	public function upload_merchant_photos($merchant_id) {
		$this -> load -> library('upload');

		$photo_path = 'merchant_photos/' . $merchant_id;
		if (!file_exists($photo_path )) {
			mkdir($photo_path , 0777, true);
		}

		$photo_thumb_path = $photo_path . '/thumbs';
		if (!file_exists($photo_thumb_path)) {
			mkdir($photo_thumb_path, 0777, true);
		}

		$config['upload_path'] = './' . $photo_path . '/';
		$config['allowed_types'] = 'gif|jpg|jpeg|png';
		$config['max_size'] = '0';
		$this -> upload -> initialize($config);

		if ($this -> upload -> do_upload('fileToUpload')) {
			$image_data = $this -> upload -> data();

			$data['image'] = $image_data['file_name'];

			$thumb_config = array('source_image' => $image_data['full_path'], //get original image
			'new_image' => $photo_thumb_path, //save as new image //need to create thumbs first
			'width' => 200, 'height' => 200);
			$this -> load -> library('image_lib', $thumb_config);
			$this -> image_lib -> resize();
			
			$saveData['merchant_id'] = $merchant_id;
			$saveData['photo_url'] = base_url($photo_path).'/'.$data['image'];
			$saveData['view_order'] = '`view_order+1`';
			
			$this -> general -> set_table('merchant_photo');
			$data['photo_merchant_id'] = $this -> general -> save($saveData);
			
			$data['url'] = $saveData['photo_url']; 
			$data['thumb_url'] = $saveData['photo_url'];
			
			$data['success'] = 1;
		} else {
			$data['msg'] = $this -> upload -> display_errors('', '');
			$data['success'] = 0;
		}
		echo json_encode($data);
	}
	
	public function updateMerchantPhotoViewOrder($merchant_id) {
		$merchant_photos = $this -> input -> post('data');
		$this -> general -> set_table('merchant_photo');
		if ($merchant_photos) {
			foreach ($merchant_photos as $img_data) {
				$data['view_order'] = $img_data['view_order'];
				$condition['merchant_photo_id'] = $img_data['id'];
				$condition['merchant_id'] = $merchant_id;
				$this -> general -> update($data, $condition);
			}
		}
	}

	public function deleteMerchantPhoto($merchant_id) {
		if ($merchant_id) {
			$this -> general -> set_table('merchant_photo');
			$condition['merchant_photo_id'] = $this -> input -> post('id');
			$condition['merchant_id'] = $merchant_id;
			if ($imgData = $this -> general -> get("", $condition)) {
				$this -> general -> delete($condition);
			}

			$data['success'] = 1;
		} else {
			$data['success'] = 0;
		}
		echo json_encode($data);
	}
}
?>
