<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Util\TImage;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFile;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Form\TSlider;
use Adianti\Wrapper\BootstrapFormBuilder;

class StoreSettingsForm extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct($param);
        
        // --- 1. Busca todos os dados de personalização do banco de dados ---
        try {
            TTransaction::open('permission');
            $all_colors = CustomColor::getObjects();
            $all_fonts = CustomFont::getObjects();
            $all_hovers = CustomHoverEffect::getObjects();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', 'Falha ao carregar dados de personalização: ' . $e->getMessage());
            return;
        }

        // --- 2. Prepara e injeta os assets dinâmicos (fontes e CSS) ---
        foreach ($all_fonts as $font) {
            TPage::include_css($font->import_url);
        }
        
        $style = new TElement('style');
        foreach ($all_hovers as $hover) {
            $style->add($hover->css_code . "\n");
        }
        parent::add($style);

        // --- 3. Monta o formulário ---
        $hbox = new THBox;
        $hbox->style = 'width: 100%; display: flex';

        $this->form = new BootstrapFormBuilder('form_store_settings');
        $this->form->enctype = 'multipart/form-data';
        
        // --- Campos do Formulário ---
        $id                 = new THidden('id');
        $theme_id           = new THidden('theme_id');
        $nome_loja          = new TEntry('nome_loja');
        $url_logo           = new TFile('url_logo');
        $background_mode    = new TRadioGroup('background_mode');
        $primary_color      = new TCombo('primary_color');
        $secondary_color    = new TCombo('secondary_color');
        $font_ui            = new TCombo('font_ui');
        $has_box_shadow     = new TRadioGroup('has_box_shadow');
        $border_radius_px   = new TSlider('border_radius_px');
        $hover_effect       = new TCombo('hover_effect');

        $nome_loja->addValidation('Nome da Loja', new TRequiredValidator);
        $url_logo->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg']);
  
        $logo_container = new THBox;
        $logo_container->add($url_logo);
        
        $background_mode->addItems(['light' => 'Claro (Light)', 'dark' => 'Escuro (Dark)']);
        $background_mode->setLayout('horizontal');
        $background_mode->setValue('light');
        
        $has_box_shadow->addItems(['1' => 'Sim', '0' => 'Não']);
        $has_box_shadow->setLayout('horizontal');
        $has_box_shadow->setValue('1');
        
        $border_radius_px->setRange(0, 24, 1);
        
        $font_ui->addItems(array_column($all_fonts, 'label', 'name'));
        $hover_effect->addItems(array_column($all_hovers, 'label', 'name'));

        $this->form->addFields([$id, $theme_id]);
        $this->form->addFields([new TLabel('Nome da Loja', '#ff0000')], [$nome_loja]);
        $this->form->addFields([new TLabel('Logo')], [$url_logo]);
        $this->form->addFields([new TLabel('Modo de Aparência')], [$background_mode]);
        $this->form->addFields([new TLabel('Cor Principal')], [$primary_color]);
        $this->form->addFields([new TLabel('Cor Secundária')], [$secondary_color]);
        $this->form->addFields([new TLabel('Fonte da Interface')], [$font_ui]);
        $this->form->addFields([new TLabel('Sombra nos Cards')], [$has_box_shadow]);
        $this->form->addFields([new TLabel('Arredondamento (px)')], [$border_radius_px]);
        $this->form->addFields([new TLabel('Efeito Hover')], [$hover_effect]);

        $this->form->addAction('Salvar Alterações', new TAction([$this, 'onSave']), 'fa:save green');
        
        // --- 4. Monta o layout da página ---
        $form_panel = TPanelGroup::pack('Edite as Configurações', $this->form);
        $hbox->add($form_panel)->style = 'width: 50%; padding-right: 10px;';
        
        $preview = new TElement('div');
        $preview->id = 'store-preview';
        $preview->style = 'padding: 20px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; justify-content: center;';
        $preview->add('
            <div id="preview-card" style="border-radius: 8px; overflow: hidden; max-width: 250px; font-family: Inter, sans-serif; transition: all 0.2s ease-in-out;">
                <img id="preview-logo" src="https://api.iconify.design/ph:mouse-duotone.svg?color=%23888888" alt="Produto" style="width:100%; height: 180px; object-fit: contain; padding: 10px; transition: background-color 0.3s;">
                <div style="padding: 15px;">
                    <h4 class="preview-title" style="margin-top:0"><b>Seu Produto</b></h4> 
                    <p>Este é um card de exemplo do seu e-commerce.</p> 
                    <div style="display: flex; gap: 10px; align-items: center;">
                       <button id="preview-button-primary" style="flex-grow: 1; border: none; padding: 10px; color: white; border-radius: 8px; cursor: pointer; transition: all 0.2s ease-in-out;">Botão Primário</button>
                       <button id="preview-button-secondary" style="flex-shrink: 0; width: 40px; height: 40px; border: 1px solid #555; padding: 10px; color: #555; background-color: transparent; border-radius: 8px; cursor: pointer; transition: all 0.2s ease-in-out; display: flex; align-items: center; justify-content: center;">
                           <i class="fa fa-heart"></i>
                       </button>
                    </div>
                </div>
            </div>
        ');
        $preview_panel = TPanelGroup::pack('Pré-visualização', $preview);
        $hbox->add($preview_panel)->style = 'width: 50%; padding-left: 10px;';

        parent::add($hbox);
        $this->onEdit($param);

        $this->injectDynamicAssets();
    }

    private function injectDynamicAssets()
    {
        try {
            TTransaction::open('permission');
            $all_colors = CustomColor::getObjects();
            $all_fonts = CustomFont::getObjects();
            $all_hovers = CustomHoverEffect::getObjects();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', 'Falha ao carregar dados de personalização: ' . $e->getMessage());
            return;
        }

        // Carrega as URLs das fontes para o navegador
        foreach ($all_fonts as $font) {
            TPage::include_css($font->import_url);
        }

        // Injeta o CSS customizado dos efeitos de hover
        $style = new TElement('style');
        foreach ($all_hovers as $hover) {
            $style->add($hover->css_code . "\n");
        }
        // Adiciona a transição padrão para suavidade
        $style->add("#preview-card, #preview-button-primary, #preview-button-secondary { transition: all 0.2s ease-out; }");
        parent::add($style);

        // Prepara os mapas de dados para o JavaScript
        $allColorsMapJS = [];
        foreach ($all_colors as $color) {
            $allColorsMapJS[$color->name] = ['label' => $color->label, 'type' => $color->type, 'light' => $color->hex_light, 'dark' => $color->hex_dark];
        }
        
        $script = new TElement('script');
        $script->type = 'text/javascript';
        $script->add("var allColorsMap = " . json_encode($allColorsMapJS) . ";");
        
        // Adiciona a lógica completa de filtragem de cores e atualização do preview
        $script->add("
            function updateColorOptions() {
                var is_dark = $('input[name=background_mode]:checked').val() === 'dark';
                var currentTheme = is_dark ? 'dark' : 'light';
                var selectedPrimary = (typeof savedPrimaryColor !== 'undefined') ? savedPrimaryColor : $('select[name=primary_color]').val();
                var selectedSecondary = (typeof savedSecondaryColor !== 'undefined') ? savedSecondaryColor : $('select[name=secondary_color]').val();

                $('select[name=primary_color], select[name=secondary_color]').empty();

                for (const name in allColorsMap) {
                    const color = allColorsMap[name];
                    if (color[currentTheme]) {
                        var option = new Option(color.label, name);
                        if (color.type === 'primary') {
                            $('select[name=primary_color]').append(option);
                        } else if (color.type === 'secondary') {
                            $('select[name=secondary_color]').append(option);
                        }
                    }
                }
                
                if ($('select[name=primary_color] option[value=\"' + selectedPrimary + '\"]').length > 0) {
                    $('select[name=primary_color]').val(selectedPrimary);
                } else {
                    // Seleciona a primeira opção para garantir que não fique nulo
                    $('select[name=primary_color] option:first').prop('selected', true);
                }

                if ($('select[name=secondary_color] option[value=\"' + selectedSecondary + '\"]').length > 0) {
                    $('select[name=secondary_color]').val(selectedSecondary);
                } else {
                    // Seleciona a primeira opção para garantir que não fique nulo
                    $('select[name=secondary_color] option:first').prop('selected', true);
                }
                $('select[name=primary_color], select[name=secondary_color]').trigger('change');
            }

            function updatePreview() {
                var is_dark = $('input[name=background_mode]:checked').val() === 'dark';
                var currentTheme = is_dark ? 'dark' : 'light';
                
                // Estilos base
                $('#store-preview').css('background-color', is_dark ? '#252527' : '#f8f9fa');
                $('#store-preview').css('border-color', is_dark ? '#a1a1aa' : '#dee2e6');
                $('#preview-card').css('background-color', is_dark ? '#3C3B3E' : '#ffffff');
                $('#preview-card').css('color', is_dark ? '#f5f5f5' : '#343a40');
                $('#preview-card').css('border', is_dark ? 'none' : '1px solid #dee2e6');
                $('#preview-logo').css('background-color', is_dark ? '#504f52' : '#f0f0f0');
                
                // Cores
                var primary_color_name = $('select[name=primary_color]').val();
                if (primary_color_name && allColorsMap[primary_color_name]) {
                    var final_primary_color = allColorsMap[primary_color_name][currentTheme];
                    $('#preview-button-primary').css('background-color', final_primary_color);
                }

                var secondary_color_name = $('select[name=secondary_color]').val();
                if (secondary_color_name && allColorsMap[secondary_color_name]) {
                    var final_secondary_color = allColorsMap[secondary_color_name][currentTheme];
                    $('#preview-button-secondary').css('border-color', final_secondary_color);
                    $('#preview-button-secondary > i').css('color', final_secondary_color);
                }
                
                // Fonte
                var font_name = $('select[name=font_ui]').val();
                $('#preview-card').css('font-family', font_name + ', sans-serif');
                
                // --- CÓDIGO COMPLETADO ---
                
                // Arredondamento
                var border_radius = $('input[name=border_radius_px]').val() + 'px';
                $('#preview-card, #preview-button-primary, #preview-button-secondary').css('border-radius', border_radius);

                // Sombra
                var has_shadow = $('input[name=has_box_shadow]:checked').val() == '1';
                var final_shadow = 'none';
                if (has_shadow) {
                    var shadow_color = is_dark ? 'rgba(0, 0, 0, 0.4)' : 'rgba(0,0,0,0.15)';
                    final_shadow = '0 4px 16px 0 ' + shadow_color;
                }
                $('#preview-card').css('box-shadow', final_shadow);

                // Efeito Hover
                var hover_effect = $('select[name=hover_effect]').val();
                $('#preview-card, #preview-button-primary, #preview-button-secondary').removeClass (function (index, className) {
                    return (className.match (/(^|\\s)hover-effect-\\S+/g) || []).join(' ');
                });
                
                if (hover_effect !== 'none') {
                    if (hover_effect === 'default') {
                        $('#preview-card').addClass('hover-effect-default-card');
                        $('#preview-button-primary, #preview-button-secondary').addClass('hover-effect-default-button');
                    }
                    else if (hover_effect === 'glow') {
                        var glow_color = is_dark ? 'rgba(255, 255, 255, 0.1)' : final_primary_color;
                        document.documentElement.style.setProperty('--shadow-color', glow_color);
                        $('#preview-card').addClass('hover-effect-glow');
                    }
                    else {
                        $('#preview-card, #preview-button-primary, #preview-button-secondary').addClass('hover-effect-' + hover_effect);
                    }
                }
                // --- FIM DO CÓDIGO COMPLETADO ---
            }
            
            // Listeners
            $('input[name=background_mode]').on('change', function() {
                updateColorOptions();
                updatePreview(); // Chama o updatePreview também para atualizar a sombra
            });
            $('form[name=form_store_settings] input, form[name=form_store_settings] select').on('change input', updatePreview);
            $(document).ready(function(){ setTimeout(updateColorOptions, 150); });
        ");
        parent::add($script);
    }

    public function onEdit($param)
    {
        try {
            TTransaction::open('permission');
            
            
            $user_id = TSession::getValue('userid');
            if ($user_id) {
                $user = SystemUser::find($user_id);
                if ($user && $user->tenant_id) {
                    $tenant = Tenant::find($user->tenant_id);
                    $theme = TenantTheme::where('tenant_id', '=', $user->tenant_id)->first();
    
                    
                    if ($tenant && $theme) {
                        $data = (object) array_merge($tenant->toArray(), $theme->toArray());
                        $data->id = $tenant->id;
                        $data->theme_id = $theme->id;
                        $this->form->setData($data);

                        $script = new TElement('script');
                        $script->add("
                            var savedPrimaryColor = '{$theme->primary_color}';
                            var savedSecondaryColor = '{$theme->secondary_color}';
                        ");
                        parent::add($script);

                        if (!empty($tenant->url_logo) && file_exists($tenant->url_logo)) {
                            
                            $script = new TElement('script');
                            $script->add(" $(document).ready(function(){ $('#preview-logo').attr('src', '{$tenant->url_logo}'); }); ");
                            parent::add($script);
                        }
                    }
                    else {
                        throw new Exception('Loja ou tema não encontrados para o ID fornecido.');
                    }
                }
                else {
                    $this->form->setEditable(FALSE);
                    new TMessage('info', 'Nenhuma loja está associada a este usuário para edição.');
                }
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', "Erro ao carregar dados: " . $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('permission');
            
            $this->form->validate();
            $data = $this->form->getData();
            
            if (empty($data->id)) {
                throw new Exception('A sessão expirou ou o ID da loja não foi encontrado. Por favor, recarregue a página.');
            }

            $tenant = new Tenant($data->id);
            
            $old_logo_path = $tenant->url_logo;
            
            if (isset($param['url_logo']) && !empty($param['url_logo'] && substr(trim($param['url_logo']), 0, 12) !== 'files/logos/'))
            {
            
                $source_file = 'tmp/' . $param['url_logo'];

                // Bloco de depuração (mantido como você pediu)
                if (!is_dir('tmp') || !is_readable('tmp')) {
                    throw new Exception("DIAGNÓSTICO: A pasta 'tmp/' não existe ou não tem permissão de leitura.");
                }
                if (!file_exists($source_file)) {
                    $files_in_tmp = implode(', ', scandir('tmp'));
                    throw new Exception("DIAGNÓSTICO: O arquivo '{$source_file}' NÃO foi encontrado. Arquivos existentes em tmp/: [{$files_in_tmp}]");
                }
                if (!is_readable($source_file)) {
                    throw new Exception("DIAGNÓSTICO: O arquivo '{$source_file}' existe, mas não pode ser lido. Verifique as permissões do arquivo.");
                }
                
                // Lógica de mover o arquivo
                $target_dir = 'files/logos/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $ext = pathinfo($source_file, PATHINFO_EXTENSION);
                $new_fileName = 'logo_' . $data->id . '_' . uniqid() . '.' . $ext;
                $target_file = $target_dir . $new_fileName;
                
                if (rename($source_file, $target_file)) {
                    // Atualiza o objeto com o novo caminho
                    $tenant->url_logo = $target_file;

                    // ADICIONADO: Deleta o arquivo antigo se ele existir, após o sucesso do novo.
                    if (!empty($old_logo_path) && file_exists($old_logo_path)) {
                        unlink($old_logo_path); // A função unlink() deleta o arquivo
                    }
                } else {
                    throw new Exception("Falha crítica ao mover o arquivo. Verifique as permissões de ESCRITA em '{$target_dir}'.");
                }
            }
        
            
            $theme = TenantTheme::where('tenant_id', '=', $data->id)->first();
            if (!$theme) {
                $theme = new TenantTheme;
                $theme->tenant_id = $data->id;
            }
            
            $tenant->nome_loja = $data->nome_loja;
            
            $theme->background_mode  = $data->background_mode;
            $theme->primary_color    = $data->primary_color;
            $theme->secondary_color  = $data->secondary_color;
            $theme->font_ui          = $data->font_ui;
            $theme->border_radius_px = $data->border_radius_px;
            $theme->has_box_shadow   = $data->has_box_shadow;
            $theme->hover_effect     = $data->hover_effect;
            
            $tenant->store();
            $theme->store();
            
            TTransaction::close();
            
            $action = new TAction([$this, 'onEdit']);
            $action->setParameter('id', $data->id);
            
            new TMessage('info', 'Configurações salvas com sucesso!', $action);

        } catch (Exception $e) {
            new TMessage('error', "Erro ao salvar: " . $e->getMessage());
            TTransaction::rollback();
        }
    }
}