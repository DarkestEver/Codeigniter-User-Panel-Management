<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . '/libraries/BaseController.php';

class Admin extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('login_model');
        $this->load->model('user_model');
        $this->datas();
        $isLoggedIn = $this->session->userdata('isLoggedIn');
        if(!isset($isLoggedIn) || $isLoggedIn != TRUE)
        {
            redirect('login');
        }
        else
        {
            if($this->isAdmin() == TRUE)
            {
                $this->accesslogincontrol();
            }
        }
    }
	
     /**
     * This function is used to load the user list
     */
    function userListing()
    {   
            $searchText = $this->security->xss_clean($this->input->post('searchText'));
            $data['searchText'] = $searchText;
            
            $this->load->library('pagination');
            
            $count = $this->user_model->userListingCount($searchText);

			$returns = $this->paginationCompress ( "userListing/", $count, 10 );
            
            $data['userRecords'] = $this->user_model->userListing($searchText, $returns["page"], $returns["segment"]);
            
            $process = 'Kullanıcı Listeleme';
            $processFunction = 'Admin/userListing';
            $this->logrecord($process,$processFunction);

            $this->global['pageTitle'] = 'BSEU : Kullanıcı Listesi';
            
            $this->loadViews("users", $this->global, $data, NULL);
    }

    /**
     * This function is used to load the add new form
     */
    function addNew()
    {
            $data['roles'] = $this->user_model->getUserRoles();

            $this->global['pageTitle'] = 'BSEU : Kullanıcı Ekle';

            $this->loadViews("addNew", $this->global, $data, NULL);
    }


    /**
     * This function is used to add new user to the system
     */
    function addNewUser()
    {
            $this->load->library('form_validation');
            
            $this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
            $this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]');
            $this->form_validation->set_rules('password','Password','required|max_length[20]');
            $this->form_validation->set_rules('cpassword','Confirm Password','trim|required|matches[password]|max_length[20]');
            $this->form_validation->set_rules('role','Role','trim|required|numeric');
            $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
            
            if($this->form_validation->run() == FALSE)
            {
                $this->addNew();
            }
            else
            {
                $name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
                $email = $this->security->xss_clean($this->input->post('email'));
                $password = $this->input->post('password');
                $roleId = $this->input->post('role');
                $mobile = $this->security->xss_clean($this->input->post('mobile'));
                
                $userInfo = array('email'=>$email, 'password'=>getHashedPassword($password), 'roleId'=>$roleId, 'name'=> $name,
                                    'mobile'=>$mobile, 'createdBy'=>$this->vendorId, 'createdDtm'=>date('Y-m-d H:i:s'));
                                    
                $result = $this->user_model->addNewUser($userInfo);
                
                if($result > 0)
                {
                    $process = 'Kullanıcı Ekleme';
                    $processFunction = 'Admin/addNewUser';
                    $this->logrecord($process,$processFunction);

                    $this->session->set_flashdata('success', 'Kullanıcı başarıyla oluşturuldu');
                }
                else
                {
                    $this->session->set_flashdata('error', 'Kullanıcı oluşturma başarısız');
                }
                
                redirect('addNew');
            }
        }

     /**
     * This function is used load user edit information
     * @param number $userId : Optional : This is user id
     */
    function editOld($userId = NULL)
    {
            if($userId == null)
            {
                redirect('userListing');
            }
            
            $data['roles'] = $this->user_model->getUserRoles();
            $data['userInfo'] = $this->user_model->getUserInfo($userId);

            $this->global['pageTitle'] = 'BSEU : Kullanıcı Düzenle';
            
            $this->loadViews("editOld", $this->global, $data, NULL);
    }


    /**
     * This function is used to edit the user information
     */
    function editUser()
    {
            $this->load->library('form_validation');
            
            $userId = $this->input->post('userId');
            
            $this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
            $this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]');
            $this->form_validation->set_rules('password','Password','matches[cpassword]|max_length[20]');
            $this->form_validation->set_rules('cpassword','Confirm Password','matches[password]|max_length[20]');
            $this->form_validation->set_rules('role','Role','trim|required|numeric');
            $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
            
            if($this->form_validation->run() == FALSE)
            {
                $this->editOld($userId);
            }
            else
            {
                $name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
                $email = $this->security->xss_clean($this->input->post('email'));
                $password = $this->input->post('password');
                $roleId = $this->input->post('role');
                $mobile = $this->security->xss_clean($this->input->post('mobile'));
                
                $userInfo = array();
                
                if(empty($password))
                {
                    $userInfo = array('email'=>$email, 'roleId'=>$roleId, 'name'=>$name,
                                    'mobile'=>$mobile, 'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
                }
                else
                {
                    $userInfo = array('email'=>$email, 'password'=>getHashedPassword($password), 'roleId'=>$roleId,
                        'name'=>ucwords($name), 'mobile'=>$mobile, 'updatedBy'=>$this->vendorId, 
                        'updatedDtm'=>date('Y-m-d H:i:s'));
                }
                
                $result = $this->user_model->editUser($userInfo, $userId);
                
                if($result == true)
                {
                    $process = 'Kullanıcı Güncelleme';
                    $processFunction = 'Admin/editUser';
                    $this->logrecord($process,$processFunction);

                    $this->session->set_flashdata('success', 'Kullanıcı başarıyla güncellendi');
                }
                else
                {
                    $this->session->set_flashdata('error', 'Kullanıcı güncelleme başarısız');
                }
                
                redirect('userListing');
            }
    }

     /**
     * This function is used to delete the user using userId
     * @return boolean $result : TRUE / FALSE
     */
    function deleteUser()
    {
            $userId = $this->input->post('userId');
            $userInfo = array('isDeleted'=>1,'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
            
            $result = $this->user_model->deleteUser($userId, $userInfo);
            
            if ($result > 0) {
                 echo(json_encode(array('status'=>TRUE)));

                 $process = 'Kullanıcı Silme';
                 $processFunction = 'Admin/deleteUser';
                 $this->logrecord($process,$processFunction);

                }
            else { echo(json_encode(array('status'=>FALSE))); }
    }

     /**
     * This function used to show login history
     * @param number $userId : This is user id
     */
    function logHistory($userId = NULL)
    {
            $data['dbinfo'] = $this->user_model->gettablemb('tbl_log','cias');
            if(isset($data['dbinfo']->total_size))
            {
                if(($data['dbinfo']->total_size)>1000){
                    $this->backupLogTable();
                }
            }
            $data['userRecords'] = $this->user_model->logHistory($userId);

            $process = 'Log Görüntüleme';
            $processFunction = 'Admin/logHistory';
            $this->logrecord($process,$processFunction);

            $this->global['pageTitle'] = 'BSEU : Kullanıcı Giriş Geçmişi';
            
            $this->loadViews("logHistory", $this->global, $data, NULL);
    }

    function logHistorysingle($userId = NULL)
    {       
            $userId = ($userId == NULL ? $this->session->userdata("userId") : $userId);
            $data["userInfo"] = $this->user_model->getUserInfoById($userId);
            $data['userRecords'] = $this->user_model->logHistory($userId);
            
            $process = 'Tekil Log Görüntüleme';
            $processFunction = 'Admin/logHistorysingle';
            $this->logrecord($process,$processFunction);

            $this->global['pageTitle'] = 'BSEU : Kullanıcı Giriş Geçmişi';
            
            $this->loadViews("logHistorysingle", $this->global, $data, NULL);      
    }
    
    function backupLogTable()
    {
        $this->load->dbutil();
        $prefs = array(
            'tables'=>array('tbl_log')
        );
        $backup=$this->dbutil->backup($prefs) ;

        date_default_timezone_set('Europe/Istanbul');
        $date = date('d-m-Y H-i');

        $filename = './backup/'.$date.'.sql.zip';
        $this->load->helper('file');
        write_file($filename,$backup);

        $this->user_model->clearlogtbl();

        if($backup)
        {
            $this->session->set_flashdata('success', 'Yedekleme ve Tablo temizleme işlemi başarılı');
            redirect('log-history');
        }
        else
        {
            $this->session->set_flashdata('error', 'Yedekleme ve Tablo temizleme işlemi başarısız');
            redirect('log-history');
        }
    }

    function logHistoryBackup()
    {
            $data['dbinfo'] = $this->user_model->gettablemb('tbl_log_backup','cias');
            if(isset($data['dbinfo']->total_size))
            {
            if(($data['dbinfo']->total_size)>1000){
                $this->backupLogTable();
            }
            }
            $data['userRecords'] = $this->user_model->logHistoryBackup();

            $process = 'Yedek Log Görüntüleme';
            $processFunction = 'Admin/logHistoryBackup';
            $this->logrecord($process,$processFunction);

            $this->global['pageTitle'] = 'BSEU : Kullanıcı Yedek Giriş Geçmişi';
            
            $this->loadViews("logHistoryBackup", $this->global, $data, NULL);
    }

    function backupLogTableDelete()
    {
        $backup=$this->user_model->clearlogBackuptbl();

        if($backup)
        {
            $this->session->set_flashdata('success', 'Tablo temizleme işlemi başarılı');
            redirect('log-history-backup');
        }
        else
        {
            $this->session->set_flashdata('error', 'Tablo temizleme işlemi başarısız');
            redirect('log-history-backup');
        }
    }

}