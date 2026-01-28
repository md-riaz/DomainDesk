<?php

namespace App\Livewire\Admin\Registrar;

use App\Models\Registrar;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RegistrarForm extends Component
{
    public ?Registrar $registrar = null;
    public $registrarId = null;
    
    public $name = '';
    public $api_class = '';
    public $credentials = [];
    public $credentialsJson = '';
    public $is_active = true;
    public $is_default = false;
    
    public $testResult = null;
    public $testMessage = '';
    public $isTesting = false;
    
    public $availableClasses = [];

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('registrars', 'name')->ignore($this->registrarId),
            ],
            'api_class' => 'required|string|max:255',
            'credentialsJson' => 'required|json',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function mount($registrarId = null)
    {
        $this->availableClasses = $this->getAvailableRegistrarClasses();
        
        if ($registrarId) {
            $this->registrarId = $registrarId;
            $this->registrar = Registrar::findOrFail($registrarId);
            
            $this->name = $this->registrar->name;
            $this->api_class = $this->registrar->api_class;
            $this->credentials = $this->registrar->credentials ?? [];
            $this->credentialsJson = json_encode($this->credentials, JSON_PRETTY_PRINT);
            $this->is_active = $this->registrar->is_active;
            $this->is_default = $this->registrar->is_default;
        } else {
            $this->credentialsJson = json_encode([
                'api_key' => '',
                'user_id' => '',
            ], JSON_PRETTY_PRINT);
        }
    }

    public function testConnection()
    {
        $this->validate();
        
        $this->isTesting = true;
        $this->testResult = null;
        $this->testMessage = '';
        
        try {
            $credentials = json_decode($this->credentialsJson, true);
            
            if (!class_exists($this->api_class)) {
                throw new \Exception("API class not found: {$this->api_class}");
            }
            
            $config = [
                'name' => $this->name,
                'slug' => Str::slug($this->name),
            ];
            
            $instance = new $this->api_class($config, $credentials);
            
            $result = $instance->testConnection();
            
            if ($result) {
                $this->testResult = 'success';
                $this->testMessage = 'Connection successful! Credentials are valid.';
            } else {
                $this->testResult = 'error';
                $this->testMessage = 'Connection test returned false.';
            }
        } catch (\Throwable $e) {
            $this->testResult = 'error';
            $this->testMessage = "Connection failed: {$e->getMessage()}";
        } finally {
            $this->isTesting = false;
        }
    }

    public function save()
    {
        $this->validate();
        
        try {
            $credentials = json_decode($this->credentialsJson, true);
            
            $data = [
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'api_class' => $this->api_class,
                'credentials' => $credentials,
                'is_active' => $this->is_active,
            ];
            
            if ($this->registrar) {
                $this->registrar->update($data);
                $message = 'Registrar updated successfully';
                
                RegistrarFactory::clearCache($this->registrar->id);
            } else {
                $this->registrar = Registrar::create($data);
                $message = 'Registrar created successfully';
            }
            
            if ($this->is_default) {
                $this->registrar->markAsDefault();
            } elseif (!$this->is_default && $this->registrar->is_default) {
                $this->registrar->update(['is_default' => false]);
            }
            
            auditLog($this->registrarId ? 'Updated registrar' : 'Created registrar', $this->registrar);
            
            session()->flash('success', $message);
            
            return redirect()->route('admin.registrars.list');
        } catch (\Throwable $e) {
            $this->addError('general', 'Failed to save registrar: ' . $e->getMessage());
        }
    }

    protected function getAvailableRegistrarClasses(): array
    {
        return [
            'App\\Services\\Registrar\\MockRegistrar' => 'Mock Registrar (Testing)',
            'App\\Services\\Registrar\\ResellerClubRegistrar' => 'ResellerClub / LogicBoxes',
        ];
    }

    public function render()
    {
        return view('livewire.admin.registrar.registrar-form')->layout('layouts.admin', [
            'title' => $this->registrar ? 'Edit Registrar' : 'Add Registrar',
            'breadcrumbs' => [
                ['label' => 'Registrars', 'url' => route('admin.registrars.list')],
                ['label' => $this->registrar ? 'Edit' : 'Add'],
            ],
        ]);
    }
}
