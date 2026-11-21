<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/**
 * Name, description, logo and color of a client (F-03, RN-12).
 *
 * The logo is stored on the public disk under the workspace's folder.
 */
class ClientForm extends Form
{
    public const string LOGO_DISK = 'public';

    public ?Client $client = null;

    public ?Workspace $workspace = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $color = null;

    /**
     * New logo picked in the upload field.
     */
    public ?TemporaryUploadedFile $logo = null;

    public bool $removeLogo = false;

    public function setWorkspace(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
        $this->workspace = $client->workspace;
        $this->name = $client->name;
        $this->description = $client->description;
        $this->color = $client->color;
        $this->logo = null;
        $this->removeLogo = false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Client::class)
                    ->where('workspace_id', $this->workspace?->id)
                    ->whereNull('deleted_at')
                    ->ignore($this->client?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'color' => 'color',
            'logo' => 'logo',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'color.regex' => 'El color debe tener el formato #RRGGBB.',
        ];
    }

    public function store(User $creator): Client
    {
        $validated = $this->validate();

        $client = $this->workspace->clients()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'color' => $validated['color'] !== null ? strtolower($validated['color']) : null,
            'created_by_id' => $creator->id,
        ]);

        $this->storeLogo($client);
        $this->reset('client', 'name', 'description', 'color', 'logo', 'removeLogo');

        return $client;
    }

    public function update(): void
    {
        $validated = $this->validate();

        $this->client->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'color' => $validated['color'] !== null ? strtolower($validated['color']) : null,
        ]);

        if ($this->removeLogo && $this->logo === null && $this->client->logo_path !== null) {
            Storage::disk(self::LOGO_DISK)->delete($this->client->logo_path);
            $this->client->update(['logo_path' => null]);
        }

        $this->storeLogo($this->client);
        $this->reset('client', 'name', 'description', 'color', 'logo', 'removeLogo');
    }

    private function storeLogo(Client $client): void
    {
        if ($this->logo === null) {
            return;
        }

        $previous = $client->logo_path;
        $path = $this->logo->storeAs(
            "workspaces/{$client->workspace->uuid}/clients/{$client->uuid}",
            'logo-'.now()->timestamp.'.'.$this->logo->getClientOriginalExtension(),
            self::LOGO_DISK,
        );

        $client->update(['logo_path' => $path]);

        if ($previous !== null) {
            Storage::disk(self::LOGO_DISK)->delete($previous);
        }
    }
}
