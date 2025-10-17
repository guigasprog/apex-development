<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Core\AdiantiCoreApplication;

class SystemLogDashboard extends TPage
{
    protected $form;

    function __construct($param)
    {
        parent::__construct();
        
        try
        {
            $this->form = new BootstrapFormBuilder('form_log_dashboard');
            
            $date_from = new TDate('date_from');
            $date_to = new TDate('date_to');
            
            $date_from->setMask('dd/mm/yyyy');
            $date_to->setMask('dd/mm/yyyy');

            $this->form->addFields( [new TLabel('Data de')], [$date_from], [new TLabel('Data até')], [$date_to] );
            $this->form->addAction('Gerar', new TAction([$this, 'onGenerate']), 'fa:check-circle green');

            $data = TSession::getValue('SystemLogDashboard_filter_data');
            
            if (empty($data))
            {
                $data = new stdClass;
                $data->date_from = date('Y-m-d');
                $data->date_to = date('Y-m-d');
                TSession::setValue('SystemLogDashboard_filter_data', $data);
            }
            
            // Define o valor para exibição com o formato correto (dd/mm/yyyy)
            $date_from->setValue(TDate::convertToMask($data->date_from, 'yyyy-mm-dd', 'dd/mm/yyyy'));
            $date_to->setValue(TDate::convertToMask($data->date_to, 'yyyy-mm-dd', 'dd/mm/yyyy'));

            // --- CORREÇÃO AQUI ---
            // A linha abaixo estava causando o bug. Remova ou comente ela.
            // $this->form->setData($data);

            // O restante do código permanece igual...

            $html = new THtmlRenderer('app/resources/system/log/dashboard.html');
            
            TTransaction::open('log');
            
            // --- INÍCIO DA CORREÇÃO ---
            // Removemos os objetos TCriteria e usamos a sintaxe fluente com where()
            
            $accesses = SystemAccessLog::where('login_time', '>=', $data->date_from . ' 00:00:00')
                                       ->where('login_time', '<=', $data->date_to . ' 23:59:59')
                                       ->count();

            $sqllogs = SystemSqlLog::where('logdate', '>=', $data->date_from . ' 00:00:00')
                                   ->where('logdate', '<=', $data->date_to . ' 23:59:59')
                                   ->count();

            $reqlogs = SystemRequestLog::where('logdate', '>=', $data->date_from . ' 00:00:00')
                                     ->where('logdate', '<=', $data->date_to . ' 23:59:59')
                                     ->count();

            $reqavg = SystemRequestLog::where('logdate', '>=', $data->date_from . ' 00:00:00')
                                    ->where('logdate', '<=', $data->date_to . ' 23:59:59')
                                    ->avgBy('request_duration');
            // --- FIM DA CORREÇÃO ---

            $indicator1 = new THtmlRenderer('app/resources/info-box.html');
            $indicator2 = new THtmlRenderer('app/resources/info-box.html');
            $indicator3 = new THtmlRenderer('app/resources/info-box.html');
            $indicator4 = new THtmlRenderer('app/resources/info-box.html');
            
            $indicator1->enableSection('main', ['title' => 'Acessos', 'icon' => 'sign-in-alt', 'background' => 'green', 'value' => $accesses]);
            $indicator2->enableSection('main', ['title' => 'Requisições', 'icon' => 'globe', 'background' => 'purple', 'value' => $reqlogs]);
            $indicator3->enableSection('main', ['title' => 'Tempo médio (ms)', 'icon' => 'hourglass-end', 'background' => 'orange', 'value' => round( (float) $reqavg,2) ]);
            $indicator4->enableSection('main', ['title' => 'Logs de SQL', 'icon' => 'database', 'background' => 'blue', 'value' => $sqllogs]);
            
            $stats1 = SystemAccessLog::where('login_time', '>=', $data->date_from . ' 00:00:00')
                                     ->where('login_time', '<=', $data->date_to . ' 23:59:59')
                                     ->groupBy('login_day')->countBy('id', 'count');

            $chart1 = new THtmlRenderer('app/resources/google_column_chart.html');
            $data1 = [];
            $data1[] = [ 'Dia', 'Contagem' ];
            if ($stats1) {
                foreach ($stats1 as $row) {
                    $data1[] = [ $row->login_day, (int) $row->count];
                }
            }
            $chart1->enableSection('main', ['data' => json_encode($data1), 'width' => '100%', 'height' => '300px', 'title' => 'Acessos por dia', 'uniqid' => uniqid(), 'ytitle' => 'Acessos', 'xtitle' => 'Dia']);
            
            $html->enableSection('main', ['indicator1' => $indicator1, 'indicator2' => $indicator2, 'indicator3' => $indicator3, 'indicator4' => $indicator4,
                                          'chart1'     => $stats1 ? $chart1 : TPanelGroup::pack('Acessos por dia', 'Sem logs'),
                                          'table1'     => '', 'chart2' => '', 'chart3' => '', 'chart4' => '', 'table2' => ''] );
            
            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu-admin.xml', __CLASS__));
            $container->add($this->form);
            $container->add($html);
            
            parent::add($container);
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onGenerate($param)
    {
        $data = (object) $param;
        $data->date_from = TDate::convertToMask($data->date_from, 'dd/mm/yyyy', 'yyyy-mm-dd');
        $data->date_to = TDate::convertToMask($data->date_to, 'dd/mm/yyyy', 'yyyy-mm-dd');
        
        TSession::setValue('SystemLogDashboard_filter_data', $data);
        AdiantiCoreApplication::loadPage(__CLASS__);
    }
}