<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . '/libraries/BaseController.php';
/**
 * Class : Login (LoginController)
 * Login class to control to authenticate user credentials and starts user's session.
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */
class Manager extends BaseController
{
    /**
     * This is default constructor of the class
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->datas();
        $isLoggedIn = $this->session->userdata('isLoggedIn');
        if(!isset($isLoggedIn) || $isLoggedIn != TRUE)
        {
            redirect('login');
        }
        else
        {
            if($this->isManagerOrAdmin() == TRUE)
            {
                $this->accesslogincontrol();
            }
        }
    }
        
     /**
     * This function used to show tasks
     */
    function tasks()
    {
            $data['taskRecords'] = $this->user_model->getTasks();

            $process = 'Tüm görevler';
            $processFunction = 'Manager/tasks';
            $this->logrecord($process,$processFunction);

            $this->global['pageTitle'] = 'BSEU : Tüm Görevler';
            
            $this->loadViews("tasks", $this->global, $data, NULL);
    }

    /**
     * This function is used to load the add new task
     */
    function addNewTask()
    {
            $data['tasks_prioritys'] = $this->user_model->getTasksPrioritys();

            $this->global['pageTitle'] = 'BSEU : Görev Ekle';

            $this->loadViews("addNewTask", $this->global, $data, NULL);
    }

     /**
     * This function is used to add new task to the system
     */
    function addNewTasks()
    {
            $this->load->library('form_validation');
            
            $this->form_validation->set_rules('fname','Görev Başlığı','required');
            $this->form_validation->set_rules('priority','Öncelik','required');
            
            if($this->form_validation->run() == FALSE)
            {
                $this->addNewTask();
            }
            else
            {
                $title = $this->input->post('fname');
                $comment = $this->input->post('comment');
                $priorityId = $this->input->post('priority');
                $statusId = 1;
                $permalink = sef($title);
                
                $taskInfo = array('title'=>$title, 'comment'=>$comment, 'priorityId'=>$priorityId, 'statusId'=> $statusId,
                                    'permalink'=>$permalink, 'createdBy'=>$this->vendorId, 'createdDtm'=>date('Y-m-d H:i:s'));
                                    
                $result = $this->user_model->addNewTasks($taskInfo);
                
                if($result > 0)
                {
                    $process = 'Görev Ekleme';
                    $processFunction = 'Manager/addNewTasks';
                    $this->logrecord($process,$processFunction);

                    $this->session->set_flashdata('success', 'Görev başarıyla oluşturuldu');
                }
                else
                {
                    $this->session->set_flashdata('error', 'Görev oluşturma başarısız');
                }
                
                redirect('addNewTask');
            }
        }


}