<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\CalendarAction;
use App\Models\CalendarEvent;
use App\Models\CalendarType;
use App\Models\CompanySetting;
use App\Models\Contact;
use App\Models\ContactRole;
use App\Models\Country;
use App\Models\Entity;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\VatRate;
use App\Support\LineItemManager;
use App\Support\SearchHash;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FluxioDemoSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call(FluxioSeeder::class);

        $countries = Country::query()->get()->keyBy('iso_code');
        $contactRoles = ContactRole::query()->orderBy('id')->get()->values();
        $vatRates = VatRate::query()->orderBy('rate')->get()->keyBy(fn (VatRate $rate): string => (string) (int) $rate->rate);
        $calendarTypes = CalendarType::query()->orderBy('id')->get()->values();
        $calendarActions = CalendarAction::query()->orderBy('id')->get()->values();

        $this->seedCompany();

        $roles = $this->seedPermissionGroups();
        $users = $this->seedUsers($roles);
        $entities = $this->seedEntities($countries);
        $this->seedContacts($entities, $contactRoles);
        $articles = $this->seedArticles($vatRates);
        $documents = $this->seedDocuments($entities, $articles);
        $this->seedInvoices($documents['supplier_orders']);
        $this->seedCalendar($users, $entities, $calendarTypes, $calendarActions);
        $this->seedActivityLog($users, $entities);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedCompany(): void
    {
        $company = CompanySetting::query()->firstOrCreate([]);

        $company->fill([
            'name' => 'Fluxio by InovCorp',
            'address' => 'Rua do Conhecimento 18',
            'postal_code' => '1600-210',
            'city' => 'Lisboa',
            'tax_number' => '516204870',
        ]);

        $company->logo_path = $this->storeCompanyLogo();
        $company->save();
    }

    /**
     * @return array<string, Role>
     */
    private function seedPermissionGroups(): array
    {
        $roles = [
            'Administrador' => Permission::query()->pluck('name')->all(),
            'Comercial' => [
                'dashboard.read',
                'clientes.create',
                'clientes.read',
                'clientes.update',
                'fornecedores.read',
                'contactos.create',
                'contactos.read',
                'contactos.update',
                'propostas.create',
                'propostas.read',
                'propostas.update',
                'encomendas-clientes.create',
                'encomendas-clientes.read',
                'encomendas-clientes.update',
                'artigos.read',
                'calendario.create',
                'calendario.read',
                'calendario.update',
                'empresa.read',
            ],
            'Financeiro' => [
                'dashboard.read',
                'financeiro.read',
                'contas-bancarias.create',
                'contas-bancarias.read',
                'contas-bancarias.update',
                'conta-corrente-clientes.read',
                'faturas-fornecedores.create',
                'faturas-fornecedores.read',
                'faturas-fornecedores.update',
                'encomendas-clientes.read',
                'encomendas-fornecedores.read',
                'propostas.read',
            ],
            'Operacoes' => [
                'dashboard.read',
                'clientes.create',
                'clientes.read',
                'clientes.update',
                'fornecedores.create',
                'fornecedores.read',
                'fornecedores.update',
                'contactos.create',
                'contactos.read',
                'contactos.update',
                'propostas.create',
                'propostas.read',
                'propostas.update',
                'encomendas-clientes.create',
                'encomendas-clientes.read',
                'encomendas-clientes.update',
                'encomendas-fornecedores.create',
                'encomendas-fornecedores.read',
                'encomendas-fornecedores.update',
                'ordens-trabalho.create',
                'ordens-trabalho.read',
                'ordens-trabalho.update',
                'calendario.read',
                'artigos.read',
            ],
        ];

        $createdRoles = [];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['is_active' => true],
            );

            $role->update(['is_active' => true]);
            $role->syncPermissions($permissions);
            $createdRoles[$roleName] = $role;
        }

        return $createdRoles;
    }

    /**
     * @param  array<string, Role>  $roles
     * @return array<string, User>
     */
    private function seedUsers(array $roles): array
    {
        $definitions = [
            'admin' => ['name' => 'Administrador Fluxio', 'email' => 'admin@fluxio.test', 'mobile' => '910000001', 'role' => 'Administrador'],
            'comercial' => ['name' => 'Marta Silva', 'email' => 'comercial@fluxio.test', 'mobile' => '910000002', 'role' => 'Comercial'],
            'financeiro' => ['name' => 'Pedro Costa', 'email' => 'financeiro@fluxio.test', 'mobile' => '910000003', 'role' => 'Financeiro'],
            'operacoes' => ['name' => 'Joao Ramos', 'email' => 'operacoes@fluxio.test', 'mobile' => '910000004', 'role' => 'Operacoes'],
        ];

        $users = [];

        foreach ($definitions as $key => $definition) {
            $user = User::query()->firstOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => Hash::make('Fluxio123!demo'),
                    'mobile' => $definition['mobile'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->fill([
                'name' => $definition['name'],
                'mobile' => $definition['mobile'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->save();

            if (isset($roles[$definition['role']])) {
                $user->syncRoles([$roles[$definition['role']]->name]);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @param  Collection<string, Country>  $countries
     * @return array<string, Entity>
     */
    private function seedEntities(Collection $countries): array
    {
        $definitions = [
            ['key' => 'atelier-norte', 'number' => 1001, 'nif' => '516000101', 'name' => 'Atelier Norte', 'address' => 'Avenida do Mar 45', 'postal_code' => '4450-718', 'city' => 'Matosinhos', 'country' => 'PT', 'phone' => '229000101', 'mobile' => '910100101', 'website' => 'https://ateliernorte.pt', 'email' => 'geral@ateliernorte.pt', 'is_customer' => true, 'is_supplier' => false, 'notes' => 'Cliente focado em projetos de mobiliario corporativo.'],
            ['key' => 'casa-horizonte', 'number' => 1002, 'nif' => '516000102', 'name' => 'Casa Horizonte', 'address' => 'Rua das Oliveiras 12', 'postal_code' => '4710-123', 'city' => 'Braga', 'country' => 'PT', 'phone' => '253000102', 'mobile' => '910100102', 'website' => 'https://casahorizonte.pt', 'email' => 'compras@casahorizonte.pt', 'is_customer' => true, 'is_supplier' => false, 'notes' => 'Conta com volume medio e recorrencia mensal.'],
            ['key' => 'clinica-viver', 'number' => 1003, 'nif' => '516000103', 'name' => 'Clinica Viver', 'address' => 'Rua Central 90', 'postal_code' => '3500-601', 'city' => 'Viseu', 'country' => 'PT', 'phone' => '232000103', 'mobile' => '910100103', 'website' => 'https://clinicaviver.pt', 'email' => 'direcao@clinicaviver.pt', 'is_customer' => true, 'is_supplier' => false, 'notes' => 'Projeto em fase de proposta para rececao e gabinetes.'],
            ['key' => 'hotel-miradouro', 'number' => 1004, 'nif' => '516000104', 'name' => 'Hotel Miradouro', 'address' => 'Avenida das Lagoas 88', 'postal_code' => '8200-112', 'city' => 'Albufeira', 'country' => 'PT', 'phone' => '289000104', 'mobile' => '910100104', 'website' => 'https://hotelmiradouro.pt', 'email' => 'operacoes@hotelmiradouro.pt', 'is_customer' => true, 'is_supplier' => false, 'notes' => 'Cliente de hotelaria com renovacao em curso no lobby principal.'],
            ['key' => 'verde-urbano', 'number' => 1005, 'nif' => '516000105', 'name' => 'Verde Urbano', 'address' => 'Rua dos Jardins 24', 'postal_code' => '3810-241', 'city' => 'Aveiro', 'country' => 'PT', 'phone' => '234000105', 'mobile' => '910100105', 'website' => 'https://verdeurbano.pt', 'email' => 'projetos@verdeurbano.pt', 'is_customer' => true, 'is_supplier' => false, 'notes' => 'Espaco comercial a preparar nova zona de exposicao e atendimento.'],
            ['key' => 'studio-atlantico', 'number' => 1006, 'nif' => '516000106', 'name' => 'Studio Atlantico', 'address' => 'Rua do Design 61', 'postal_code' => '9000-091', 'city' => 'Funchal', 'country' => 'PT', 'phone' => '291000106', 'mobile' => '910100106', 'website' => 'https://studioatlantico.pt', 'email' => 'hello@studioatlantico.pt', 'is_customer' => true, 'is_supplier' => true, 'notes' => 'Parceiro criativo com componente de producao personalizada.'],
            ['key' => 'madeiras-silva', 'number' => 2001, 'nif' => '516000201', 'name' => 'Madeiras Silva', 'address' => 'Zona Industrial Norte 14', 'postal_code' => '4805-320', 'city' => 'Guimaraes', 'country' => 'PT', 'phone' => '253000201', 'mobile' => '910200201', 'website' => 'https://madeirassilva.pt', 'email' => 'vendas@madeirassilva.pt', 'is_customer' => false, 'is_supplier' => true, 'notes' => 'Fornecedor principal de estruturas em madeira e tampos.'],
            ['key' => 'texteis-costa', 'number' => 2002, 'nif' => '516000202', 'name' => 'Texteis Costa', 'address' => 'Rua da Fiacao 52', 'postal_code' => '4750-420', 'city' => 'Barcelos', 'country' => 'PT', 'phone' => '253000202', 'mobile' => '910200202', 'website' => 'https://texteiscosta.pt', 'email' => 'comercial@texteiscosta.pt', 'is_customer' => false, 'is_supplier' => true, 'notes' => 'Fornecedor de tecidos tecnicos, cortinas e revestimentos.'],
            ['key' => 'luz-linha', 'number' => 2003, 'nif' => '516000203', 'name' => 'Luz e Linha', 'address' => 'Rua da Tecnologia 7', 'postal_code' => '4400-332', 'city' => 'Vila Nova de Gaia', 'country' => 'PT', 'phone' => '223000203', 'mobile' => '910200203', 'website' => 'https://luzelinha.pt', 'email' => 'apoio@luzelinha.pt', 'is_customer' => false, 'is_supplier' => true, 'notes' => 'Fornecedor de iluminacao tecnica e decorativa.'],
            ['key' => 'metalworks-iberia', 'number' => 2004, 'nif' => '516000204', 'name' => 'Metalworks Iberia', 'address' => 'Parque Industrial Sul 19', 'postal_code' => '2430-409', 'city' => 'Marinha Grande', 'country' => 'PT', 'phone' => '244000204', 'mobile' => '910200204', 'website' => 'https://metalworksiberia.pt', 'email' => 'industrial@metalworksiberia.pt', 'is_customer' => false, 'is_supplier' => true, 'notes' => 'Estruturas metalicas e estantaria tecnica para projetos especiais.'],
            ['key' => 'oficina-criativa', 'number' => 3001, 'nif' => '516000301', 'name' => 'Oficina Criativa', 'address' => 'Rua do Atelier 3', 'postal_code' => '3000-160', 'city' => 'Coimbra', 'country' => 'PT', 'phone' => '239000301', 'mobile' => '910300301', 'website' => 'https://oficinacriativa.pt', 'email' => 'parcerias@oficinacriativa.pt', 'is_customer' => true, 'is_supplier' => true, 'notes' => 'Parceiro misto para producao local e subcontratacao.'],
        ];

        $entities = [];

        foreach ($definitions as $definition) {
            $entity = Entity::query()->firstOrNew(['number' => $definition['number']]);
            $entity->fill([
                'nif' => $definition['nif'],
                'name' => $definition['name'],
                'address' => $definition['address'],
                'postal_code' => $definition['postal_code'],
                'city' => $definition['city'],
                'country_id' => $countries->get($definition['country'])?->id,
                'phone' => $definition['phone'],
                'mobile' => $definition['mobile'],
                'website' => $definition['website'],
                'email' => $definition['email'],
                'gdpr_consent' => true,
                'notes' => $definition['notes'],
                'vies_payload' => ['valid' => true, 'source' => 'demo'],
                'is_active' => true,
                'is_customer' => $definition['is_customer'],
                'is_supplier' => $definition['is_supplier'],
            ]);
            $entity->save();

            $entities[$definition['key']] = $entity;
        }

        return $entities;
    }

    /**
     * @param  array<string, Entity>  $entities
     */
    private function seedContacts(array $entities, Collection $contactRoles): void
    {
        $definitions = [
            ['number' => 5001, 'entity' => 'atelier-norte', 'first' => 'Carla', 'last' => 'Mendes', 'role_index' => 0, 'email' => 'carla.mendes@ateliernorte.pt', 'phone' => '229000111', 'mobile' => '910500111'],
            ['number' => 5002, 'entity' => 'atelier-norte', 'first' => 'Luis', 'last' => 'Pereira', 'role_index' => 1, 'email' => 'luis.pereira@ateliernorte.pt', 'phone' => '229000112', 'mobile' => '910500112'],
            ['number' => 5003, 'entity' => 'casa-horizonte', 'first' => 'Rita', 'last' => 'Ferreira', 'role_index' => 0, 'email' => 'rita.ferreira@casahorizonte.pt', 'phone' => '253000121', 'mobile' => '910500121'],
            ['number' => 5004, 'entity' => 'clinica-viver', 'first' => 'Diana', 'last' => 'Alves', 'role_index' => 2, 'email' => 'diana.alves@clinicaviver.pt', 'phone' => '232000131', 'mobile' => '910500131'],
            ['number' => 5005, 'entity' => 'hotel-miradouro', 'first' => 'Mafalda', 'last' => 'Sousa', 'role_index' => 0, 'email' => 'mafalda.sousa@hotelmiradouro.pt', 'phone' => '289000141', 'mobile' => '910500141'],
            ['number' => 5006, 'entity' => 'verde-urbano', 'first' => 'Tiago', 'last' => 'Gomes', 'role_index' => 1, 'email' => 'tiago.gomes@verdeurbano.pt', 'phone' => '234000151', 'mobile' => '910500151'],
            ['number' => 5007, 'entity' => 'madeiras-silva', 'first' => 'Andre', 'last' => 'Silva', 'role_index' => 0, 'email' => 'andre.silva@madeirassilva.pt', 'phone' => '253000211', 'mobile' => '910500211'],
            ['number' => 5008, 'entity' => 'texteis-costa', 'first' => 'Patricia', 'last' => 'Costa', 'role_index' => 1, 'email' => 'patricia.costa@texteiscosta.pt', 'phone' => '253000221', 'mobile' => '910500221'],
            ['number' => 5009, 'entity' => 'luz-linha', 'first' => 'Hugo', 'last' => 'Martins', 'role_index' => 1, 'email' => 'hugo.martins@luzelinha.pt', 'phone' => '223000231', 'mobile' => '910500231'],
            ['number' => 5010, 'entity' => 'metalworks-iberia', 'first' => 'Sergio', 'last' => 'Leal', 'role_index' => 2, 'email' => 'sergio.leal@metalworksiberia.pt', 'phone' => '244000241', 'mobile' => '910500241'],
            ['number' => 5011, 'entity' => 'oficina-criativa', 'first' => 'Ines', 'last' => 'Lopes', 'role_index' => 3, 'email' => 'ines.lopes@oficinacriativa.pt', 'phone' => '239000251', 'mobile' => '910500251'],
            ['number' => 5012, 'entity' => 'studio-atlantico', 'first' => 'Nuno', 'last' => 'Freitas', 'role_index' => 0, 'email' => 'nuno.freitas@studioatlantico.pt', 'phone' => '291000261', 'mobile' => '910500261'],
        ];

        foreach ($definitions as $definition) {
            $contact = Contact::query()->firstOrNew(['number' => $definition['number']]);
            $contact->fill([
                'entity_id' => $entities[$definition['entity']]->id,
                'first_name' => $definition['first'],
                'last_name' => $definition['last'],
                'contact_role_id' => $contactRoles->get($definition['role_index'])?->id,
                'phone' => $definition['phone'],
                'mobile' => $definition['mobile'],
                'email' => $definition['email'],
                'gdpr_consent' => true,
                'notes' => 'Contacto demo para navegacao na aplicacao.',
                'is_active' => true,
            ]);
            $contact->save();
        }
    }

    /**
     * @param  Collection<string, VatRate>  $vatRates
     * @return array<string, array{model: Article, supplier_key: string, cost_price: float}>
     */
    private function seedArticles(Collection $vatRates): array
    {
        $definitions = [
            ['key' => 'mesa-reuniao', 'reference' => 'ART-0001', 'name' => 'Mesa de reuniao', 'description' => 'Mesa retangular para salas de reuniao.', 'price' => 1290.00, 'cost' => 840.00, 'vat' => 23, 'supplier' => 'madeiras-silva', 'photo' => true],
            ['key' => 'cadeira-ergonomica', 'reference' => 'ART-0002', 'name' => 'Cadeira ergonomica', 'description' => 'Cadeira com apoio lombar.', 'price' => 189.00, 'cost' => 112.00, 'vat' => 23, 'supplier' => 'oficina-criativa', 'photo' => true],
            ['key' => 'painel-acustico', 'reference' => 'ART-0003', 'name' => 'Painel acustico', 'description' => 'Painel decorativo com absorcao sonora.', 'price' => 245.00, 'cost' => 148.00, 'vat' => 23, 'supplier' => 'oficina-criativa', 'photo' => true],
            ['key' => 'candeeiro-pendente', 'reference' => 'ART-0004', 'name' => 'Candeeiro pendente', 'description' => 'Iluminacao suspensa para rececao.', 'price' => 320.00, 'cost' => 190.00, 'vat' => 23, 'supplier' => 'luz-linha', 'photo' => true],
            ['key' => 'balcao-rececao', 'reference' => 'ART-0005', 'name' => 'Balcao de rececao', 'description' => 'Balcao modular para zonas de entrada.', 'price' => 1980.00, 'cost' => 1375.00, 'vat' => 23, 'supplier' => 'madeiras-silva', 'photo' => false],
            ['key' => 'fita-led', 'reference' => 'ART-0006', 'name' => 'Fita LED premium', 'description' => 'Iluminacao linear para mobiliario.', 'price' => 96.00, 'cost' => 54.00, 'vat' => 23, 'supplier' => 'luz-linha', 'photo' => false],
            ['key' => 'estante-metalica', 'reference' => 'ART-0007', 'name' => 'Estante metalica', 'description' => 'Estante modular para arquivo e exposicao.', 'price' => 760.00, 'cost' => 495.00, 'vat' => 23, 'supplier' => 'metalworks-iberia', 'photo' => true],
            ['key' => 'cortina-blackout', 'reference' => 'ART-0008', 'name' => 'Cortina blackout', 'description' => 'Cortina tecnica para controlo de luz.', 'price' => 145.00, 'cost' => 88.00, 'vat' => 23, 'supplier' => 'texteis-costa', 'photo' => false],
            ['key' => 'mesa-apoio', 'reference' => 'ART-0009', 'name' => 'Mesa de apoio', 'description' => 'Mesa auxiliar para lounges e salas de espera.', 'price' => 220.00, 'cost' => 134.00, 'vat' => 23, 'supplier' => 'oficina-criativa', 'photo' => false],
            ['key' => 'biombo-decorativo', 'reference' => 'ART-0010', 'name' => 'Biombo decorativo', 'description' => 'Separador de ambientes com acabamento textil.', 'price' => 410.00, 'cost' => 255.00, 'vat' => 23, 'supplier' => 'studio-atlantico', 'photo' => true],
        ];

        $articles = [];

        foreach ($definitions as $definition) {
            $article = Article::query()->firstOrNew([
                'reference_hash' => SearchHash::make($definition['reference']),
            ]);

            $article->fill([
                'reference' => $definition['reference'],
                'name' => $definition['name'],
                'description' => $definition['description'],
                'price' => number_format($definition['price'], 2, '.', ''),
                'vat_rate_id' => $vatRates->get((string) $definition['vat'])?->id,
                'notes' => 'Artigo demo associado ao catalogo principal.',
                'is_active' => true,
            ]);

            if ($definition['photo']) {
                $article->photo_path = $this->storeArticleImage($definition['reference'], $definition['name']);
            }

            $article->save();
            $article->loadMissing('vatRate');

            $articles[$definition['key']] = [
                'model' => $article,
                'supplier_key' => $definition['supplier'],
                'cost_price' => $definition['cost'],
            ];
        }

        return $articles;
    }

    /**
     * @param  array<string, Entity>  $entities
     * @param  array<string, array{model: Article, supplier_key: string, cost_price: float}>  $articles
     * @return array{proposals: array<string, Proposal>, customer_orders: array<string, Order>, supplier_orders: array<string, Order>}
     */
    private function seedDocuments(array $entities, array $articles): array
    {
        $year = now()->year;

        $proposalDefinitions = [
            [
                'key' => 'atelier-boardroom',
                'number' => sprintf('PROP-%d-0001', $year),
                'customer' => 'atelier-norte',
                'proposal_date' => Carbon::now()->subDays(26)->toDateString(),
                'valid_until' => Carbon::now()->addDays(12)->toDateString(),
                'status' => 'closed',
                'items' => [
                    ['article' => 'mesa-reuniao', 'quantity' => 1],
                    ['article' => 'cadeira-ergonomica', 'quantity' => 8],
                    ['article' => 'candeeiro-pendente', 'quantity' => 3],
                    ['article' => 'fita-led', 'quantity' => 4],
                ],
            ],
            [
                'key' => 'casa-refresh',
                'number' => sprintf('PROP-%d-0002', $year),
                'customer' => 'casa-horizonte',
                'proposal_date' => Carbon::now()->subDays(11)->toDateString(),
                'valid_until' => Carbon::now()->addDays(19)->toDateString(),
                'status' => 'draft',
                'items' => [
                    ['article' => 'cortina-blackout', 'quantity' => 10],
                    ['article' => 'mesa-apoio', 'quantity' => 4],
                    ['article' => 'biombo-decorativo', 'quantity' => 2],
                ],
            ],
            [
                'key' => 'hotel-lobby',
                'number' => sprintf('PROP-%d-0003', $year),
                'customer' => 'hotel-miradouro',
                'proposal_date' => Carbon::now()->subDays(6)->toDateString(),
                'valid_until' => Carbon::now()->addDays(24)->toDateString(),
                'status' => 'closed',
                'items' => [
                    ['article' => 'balcao-rececao', 'quantity' => 1],
                    ['article' => 'candeeiro-pendente', 'quantity' => 6],
                    ['article' => 'cortina-blackout', 'quantity' => 12],
                    ['article' => 'painel-acustico', 'quantity' => 3],
                ],
            ],
        ];

        $proposals = [];

        foreach ($proposalDefinitions as $definition) {
            $items = $this->buildLineItems($articles, $entities, $definition['items']);

            $proposals[$definition['key']] = Proposal::query()->updateOrCreate(
                ['number' => $definition['number']],
                [
                    'proposal_date' => $definition['proposal_date'],
                    'valid_until' => $definition['valid_until'],
                    'entity_id' => $entities[$definition['customer']]->id,
                    'line_items' => $items,
                    'totals' => LineItemManager::totals($items),
                    'status' => $definition['status'],
                ],
            );
        }

        $customerOrderDefinitions = [
            [
                'key' => 'atelier-order',
                'number' => sprintf('ENCC-%d-0001', $year),
                'customer' => 'atelier-norte',
                'proposal' => 'atelier-boardroom',
                'order_date' => Carbon::now()->subDays(20)->toDateString(),
                'valid_until' => Carbon::now()->addDays(10)->toDateString(),
                'status' => 'closed',
                'items' => [
                    ['article' => 'mesa-reuniao', 'quantity' => 1],
                    ['article' => 'cadeira-ergonomica', 'quantity' => 8],
                    ['article' => 'candeeiro-pendente', 'quantity' => 3],
                    ['article' => 'fita-led', 'quantity' => 4],
                ],
            ],
            [
                'key' => 'hotel-order',
                'number' => sprintf('ENCC-%d-0002', $year),
                'customer' => 'hotel-miradouro',
                'proposal' => 'hotel-lobby',
                'order_date' => Carbon::now()->subDays(4)->toDateString(),
                'valid_until' => Carbon::now()->addDays(26)->toDateString(),
                'status' => 'draft',
                'items' => [
                    ['article' => 'balcao-rececao', 'quantity' => 1],
                    ['article' => 'candeeiro-pendente', 'quantity' => 6],
                    ['article' => 'cortina-blackout', 'quantity' => 12],
                    ['article' => 'painel-acustico', 'quantity' => 3],
                ],
            ],
            [
                'key' => 'clinica-order',
                'number' => sprintf('ENCC-%d-0003', $year),
                'customer' => 'clinica-viver',
                'proposal' => null,
                'order_date' => Carbon::now()->subDays(14)->toDateString(),
                'valid_until' => Carbon::now()->addDays(6)->toDateString(),
                'status' => 'closed',
                'items' => [
                    ['article' => 'balcao-rececao', 'quantity' => 1],
                    ['article' => 'painel-acustico', 'quantity' => 6],
                    ['article' => 'mesa-apoio', 'quantity' => 3],
                ],
            ],
            [
                'key' => 'verde-order',
                'number' => sprintf('ENCC-%d-0004', $year),
                'customer' => 'verde-urbano',
                'proposal' => null,
                'order_date' => Carbon::now()->subDays(8)->toDateString(),
                'valid_until' => Carbon::now()->addDays(14)->toDateString(),
                'status' => 'closed',
                'items' => [
                    ['article' => 'estante-metalica', 'quantity' => 4],
                    ['article' => 'fita-led', 'quantity' => 10],
                    ['article' => 'biombo-decorativo', 'quantity' => 2],
                ],
            ],
        ];

        $customerOrders = [];

        foreach ($customerOrderDefinitions as $definition) {
            $items = $this->buildLineItems($articles, $entities, $definition['items']);

            $customerOrders[$definition['key']] = Order::query()->updateOrCreate(
                ['number' => $definition['number']],
                [
                    'kind' => 'customer',
                    'order_date' => $definition['order_date'],
                    'valid_until' => $definition['valid_until'],
                    'customer_entity_id' => $entities[$definition['customer']]->id,
                    'supplier_entity_id' => null,
                    'proposal_id' => $definition['proposal'] ? $proposals[$definition['proposal']]->id : null,
                    'source_order_id' => null,
                    'line_items' => $items,
                    'totals' => LineItemManager::totals($items),
                    'status' => $definition['status'],
                ],
            );
        }

        $supplierOrderDefinitions = [
            ['key' => 'atelier-madeiras', 'number' => sprintf('ENCF-%d-0001', $year), 'source' => 'atelier-order', 'supplier' => 'madeiras-silva', 'status' => 'closed'],
            ['key' => 'atelier-oficina', 'number' => sprintf('ENCF-%d-0002', $year), 'source' => 'atelier-order', 'supplier' => 'oficina-criativa', 'status' => 'closed'],
            ['key' => 'atelier-luz', 'number' => sprintf('ENCF-%d-0003', $year), 'source' => 'atelier-order', 'supplier' => 'luz-linha', 'status' => 'draft'],
            ['key' => 'clinica-madeiras', 'number' => sprintf('ENCF-%d-0004', $year), 'source' => 'clinica-order', 'supplier' => 'madeiras-silva', 'status' => 'closed'],
            ['key' => 'clinica-oficina', 'number' => sprintf('ENCF-%d-0005', $year), 'source' => 'clinica-order', 'supplier' => 'oficina-criativa', 'status' => 'closed'],
            ['key' => 'verde-metalworks', 'number' => sprintf('ENCF-%d-0006', $year), 'source' => 'verde-order', 'supplier' => 'metalworks-iberia', 'status' => 'closed'],
            ['key' => 'verde-luz', 'number' => sprintf('ENCF-%d-0007', $year), 'source' => 'verde-order', 'supplier' => 'luz-linha', 'status' => 'draft'],
            ['key' => 'verde-studio', 'number' => sprintf('ENCF-%d-0008', $year), 'source' => 'verde-order', 'supplier' => 'studio-atlantico', 'status' => 'closed'],
        ];

        $supplierOrders = [];

        foreach ($supplierOrderDefinitions as $definition) {
            $sourceOrder = $customerOrders[$definition['source']];
            $supplierId = $entities[$definition['supplier']]->id;

            $supplierItems = array_map(
                fn (array $item): array => [
                    ...$item,
                    'unit_price' => (float) ($item['cost_price'] ?? $item['unit_price'] ?? 0),
                ],
                LineItemManager::forSupplier($sourceOrder->line_items ?? [], $supplierId),
            );

            $supplierItems = LineItemManager::normalise($supplierItems);

            if ($supplierItems === []) {
                continue;
            }

            $supplierOrders[$definition['key']] = Order::query()->updateOrCreate(
                ['number' => $definition['number']],
                [
                    'kind' => 'supplier',
                    'order_date' => $sourceOrder->order_date,
                    'valid_until' => $sourceOrder->valid_until,
                    'customer_entity_id' => $sourceOrder->customer_entity_id,
                    'supplier_entity_id' => $supplierId,
                    'proposal_id' => null,
                    'source_order_id' => $sourceOrder->id,
                    'line_items' => $supplierItems,
                    'totals' => LineItemManager::totals($supplierItems),
                    'status' => $definition['status'],
                ],
            );
        }

        return [
            'proposals' => $proposals,
            'customer_orders' => $customerOrders,
            'supplier_orders' => $supplierOrders,
        ];
    }

    /**
     * @param  array<string, Order>  $supplierOrders
     */
    private function seedInvoices(array $supplierOrders): void
    {
        $year = now()->year;

        $definitions = [
            ['number' => sprintf('FF-%d-0001', $year), 'order' => 'atelier-madeiras', 'invoice_date' => Carbon::now()->subDays(18)->toDateString(), 'due_date' => Carbon::now()->addDays(4)->toDateString(), 'status' => 'pending'],
            ['number' => sprintf('FF-%d-0002', $year), 'order' => 'atelier-oficina', 'invoice_date' => Carbon::now()->subDays(17)->toDateString(), 'due_date' => Carbon::now()->subDays(2)->toDateString(), 'status' => 'paid'],
            ['number' => sprintf('FF-%d-0003', $year), 'order' => 'clinica-madeiras', 'invoice_date' => Carbon::now()->subDays(10)->toDateString(), 'due_date' => Carbon::now()->addDays(9)->toDateString(), 'status' => 'pending'],
            ['number' => sprintf('FF-%d-0004', $year), 'order' => 'verde-metalworks', 'invoice_date' => Carbon::now()->subDays(5)->toDateString(), 'due_date' => Carbon::now()->addDays(16)->toDateString(), 'status' => 'paid'],
        ];

        foreach ($definitions as $definition) {
            $order = $supplierOrders[$definition['order']] ?? null;

            if (! $order) {
                continue;
            }

            $invoice = SupplierInvoice::query()->firstOrNew(['number' => $definition['number']]);
            $invoice->fill([
                'invoice_date' => $definition['invoice_date'],
                'due_date' => $definition['due_date'],
                'supplier_entity_id' => $order->supplier_entity_id,
                'supplier_order_id' => $order->id,
                'total' => number_format((float) ($order->totals['total'] ?? 0), 2, '.', ''),
                'status' => $definition['status'],
            ]);

            $invoice->document_path = $this->storeInvoiceDocument($definition['number'], $order->supplier?->name ?? 'Fornecedor');
            $invoice->payment_proof_path = $definition['status'] === 'paid'
                ? $this->storePaymentProof($definition['number'], $order->supplier?->name ?? 'Fornecedor')
                : null;

            $invoice->save();
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Entity>  $entities
     */
    private function seedCalendar(array $users, array $entities, Collection $calendarTypes, Collection $calendarActions): void
    {
        $definitions = [
            ['user' => 'comercial', 'entity' => 'atelier-norte', 'type_index' => 1, 'action_index' => 1, 'scheduled_for' => Carbon::now()->subDays(2)->setTime(10, 0), 'duration' => 90, 'shared' => true, 'knowledge' => true, 'status' => 'completed', 'description' => 'Reuniao de alinhamento final da proposta da sala de reuniao.'],
            ['user' => 'operacoes', 'entity' => 'hotel-miradouro', 'type_index' => 0, 'action_index' => 0, 'scheduled_for' => Carbon::now()->addDay()->setTime(14, 30), 'duration' => 60, 'shared' => true, 'knowledge' => false, 'status' => 'scheduled', 'description' => 'Visita tecnica ao lobby para confirmar medidas e acessos.'],
            ['user' => 'financeiro', 'entity' => 'madeiras-silva', 'type_index' => 1, 'action_index' => 2, 'scheduled_for' => Carbon::now()->addDays(2)->setTime(9, 15), 'duration' => 45, 'shared' => false, 'knowledge' => true, 'status' => 'scheduled', 'description' => 'Follow-up sobre condicoes de pagamento da fatura FF demo.'],
            ['user' => 'admin', 'entity' => 'verde-urbano', 'type_index' => 2, 'action_index' => 0, 'scheduled_for' => Carbon::now()->addDays(4)->setTime(16, 0), 'duration' => 120, 'shared' => true, 'knowledge' => true, 'status' => 'scheduled', 'description' => 'Entrega parcial de estantes e validacao com o cliente.'],
            ['user' => 'comercial', 'entity' => 'casa-horizonte', 'type_index' => 1, 'action_index' => 1, 'scheduled_for' => Carbon::now()->addDays(6)->setTime(11, 0), 'duration' => 30, 'shared' => false, 'knowledge' => false, 'status' => 'scheduled', 'description' => 'Follow-up comercial sobre a proposta em rascunho.'],
            ['user' => 'operacoes', 'entity' => 'studio-atlantico', 'type_index' => 0, 'action_index' => 2, 'scheduled_for' => Carbon::now()->subDay()->setTime(8, 45), 'duration' => 30, 'shared' => false, 'knowledge' => true, 'status' => 'cancelled', 'description' => 'Visita cancelada por reajuste de agenda do parceiro.'],
        ];

        foreach ($definitions as $definition) {
            $event = CalendarEvent::query()->firstOrNew([
                'user_id' => $users[$definition['user']]->id,
                'entity_id' => $entities[$definition['entity']]->id,
                'scheduled_for' => $definition['scheduled_for']->format('Y-m-d H:i:s'),
            ]);

            $event->fill([
                'calendar_type_id' => $calendarTypes->get($definition['type_index'])?->id,
                'calendar_action_id' => $calendarActions->get($definition['action_index'])?->id,
                'duration_minutes' => $definition['duration'],
                'shared' => $definition['shared'],
                'knowledge' => $definition['knowledge'],
                'description' => $definition['description'],
                'status' => $definition['status'],
            ]);

            $event->save();
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Entity>  $entities
     */
    private function seedActivityLog(array $users, array $entities): void
    {
        Activity::query()->where('log_name', 'fluxio-demo')->delete();

        $definitions = [
            ['user' => 'admin', 'menu' => 'Empresa', 'action' => 'update', 'device' => 'Desktop', 'ip' => '127.0.0.1', 'subject' => $entities['atelier-norte'], 'at' => Carbon::now()->subDays(5)->setTime(9, 4)],
            ['user' => 'comercial', 'menu' => 'Propostas', 'action' => 'create', 'device' => 'Desktop', 'ip' => '127.0.0.1', 'subject' => $entities['hotel-miradouro'], 'at' => Carbon::now()->subDays(4)->setTime(11, 20)],
            ['user' => 'comercial', 'menu' => 'Clientes', 'action' => 'update', 'device' => 'Mobile', 'ip' => '10.0.0.24', 'subject' => $entities['casa-horizonte'], 'at' => Carbon::now()->subDays(3)->setTime(14, 12)],
            ['user' => 'operacoes', 'menu' => 'Encomendas Clientes', 'action' => 'convert', 'device' => 'Desktop', 'ip' => '127.0.0.1', 'subject' => $entities['verde-urbano'], 'at' => Carbon::now()->subDays(2)->setTime(16, 45)],
            ['user' => 'financeiro', 'menu' => 'Faturas Fornecedor', 'action' => 'update', 'device' => 'Desktop', 'ip' => '127.0.0.1', 'subject' => $entities['madeiras-silva'], 'at' => Carbon::now()->subDays(1)->setTime(10, 18)],
            ['user' => 'admin', 'menu' => 'Utilizadores', 'action' => 'create', 'device' => 'Tablet', 'ip' => '10.0.0.17', 'subject' => $users['operacoes'], 'at' => Carbon::now()->subDay()->setTime(15, 6)],
            ['user' => 'operacoes', 'menu' => 'Calendario', 'action' => 'create', 'device' => 'Desktop', 'ip' => '127.0.0.1', 'subject' => $entities['studio-atlantico'], 'at' => Carbon::now()->subHours(9)],
            ['user' => 'financeiro', 'menu' => 'Logs', 'action' => 'read', 'device' => 'Desktop', 'ip' => '127.0.0.1', 'subject' => null, 'at' => Carbon::now()->subHours(2)],
        ];

        foreach ($definitions as $definition) {
            $activity = new Activity;
            $activity->forceFill([
                'log_name' => 'fluxio-demo',
                'description' => $definition['action'],
                'event' => $definition['action'],
                'subject_type' => $definition['subject'] ? $definition['subject']::class : null,
                'subject_id' => $definition['subject']?->getKey(),
                'causer_type' => User::class,
                'causer_id' => $users[$definition['user']]->id,
                'attribute_changes' => null,
                'properties' => [
                    'menu' => $definition['menu'],
                    'action' => $definition['action'],
                    'device' => $definition['device'],
                    'ip' => $definition['ip'],
                ],
                'created_at' => $definition['at'],
                'updated_at' => $definition['at'],
            ]);
            $activity->save();
        }
    }

    /**
     * @param  array<string, array{model: Article, supplier_key: string, cost_price: float}>  $articles
     * @param  array<string, Entity>  $entities
     * @param  array<int, array<string, mixed>>  $definitions
     * @return array<int, array<string, mixed>>
     */
    private function buildLineItems(array $articles, array $entities, array $definitions): array
    {
        return LineItemManager::normalise(array_map(function (array $definition) use ($articles, $entities): array {
            $articleMeta = $articles[$definition['article']];
            $article = $articleMeta['model'];
            $supplierKey = $definition['supplier'] ?? $articleMeta['supplier_key'];

            return [
                'article_id' => $article->id,
                'supplier_entity_id' => $entities[$supplierKey]->id ?? null,
                'reference' => $article->reference,
                'name' => $article->name,
                'description' => $definition['description'] ?? $article->description,
                'quantity' => $definition['quantity'],
                'unit_price' => $definition['unit_price'] ?? (float) $article->price,
                'cost_price' => $definition['cost_price'] ?? $articleMeta['cost_price'],
                'vat_rate' => $definition['vat_rate'] ?? (float) ($article->vatRate?->rate ?? 0),
            ];
        }, $definitions));
    }

    private function storeCompanyLogo(): string
    {
        $path = 'company/fluxio-demo-logo.svg';
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 120" role="img" aria-labelledby="title">
  <title>Fluxio Demo</title>
  <rect width="320" height="120" rx="26" fill="#f4ead7"/>
  <rect x="16" y="16" width="68" height="88" rx="24" fill="#3f4353"/>
  <circle cx="50" cy="60" r="20" fill="#f4ead7" stroke="#9ca3af" stroke-width="2"/>
  <text x="50" y="66" text-anchor="middle" fill="#3f4353" font-size="16" font-family="Arial, sans-serif" font-weight="700">FX</text>
  <text x="106" y="50" fill="#3f2d26" font-size="28" font-family="Georgia, serif" font-weight="700">Fluxio</text>
  <text x="106" y="76" fill="#8a8379" font-size="14" font-family="Arial, sans-serif" letter-spacing="4">OPERATIONS HUB</text>
</svg>
SVG;

        Storage::disk('local')->put($path, $svg);

        return $path;
    }

    private function storeArticleImage(string $reference, string $name): string
    {
        $path = sprintf('articles/photos/%s.svg', strtolower($reference));
        $label = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $shortReference = htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 180" role="img" aria-labelledby="title">
  <title>{$shortReference}</title>
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#fbf4e7"/>
      <stop offset="100%" stop-color="#d9c4a0"/>
    </linearGradient>
  </defs>
  <rect width="240" height="180" rx="24" fill="url(#bg)"/>
  <rect x="18" y="18" width="204" height="144" rx="20" fill="#485064" opacity="0.9"/>
  <text x="120" y="74" text-anchor="middle" fill="#f8f2e7" font-size="18" font-family="Arial, sans-serif" font-weight="700">{$shortReference}</text>
  <text x="120" y="104" text-anchor="middle" fill="#f8f2e7" font-size="15" font-family="Georgia, serif">{$label}</text>
  <text x="120" y="132" text-anchor="middle" fill="#d9c4a0" font-size="11" font-family="Arial, sans-serif" letter-spacing="2">FLUXIO DEMO</text>
</svg>
SVG;

        Storage::disk('local')->put($path, $svg);

        return $path;
    }

    private function storeInvoiceDocument(string $number, string $supplierName): string
    {
        $path = sprintf('supplier-invoices/documents/%s.txt', strtolower($number));
        $contents = implode(PHP_EOL, [
            'Fluxio Demo',
            sprintf('Documento associado a %s', $number),
            sprintf('Fornecedor: %s', $supplierName),
            'Este ficheiro foi gerado apenas para demonstracao da aplicacao.',
        ]);

        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    private function storePaymentProof(string $number, string $supplierName): string
    {
        $path = sprintf('supplier-invoices/payment-proofs/%s.txt', strtolower($number));
        $contents = implode(PHP_EOL, [
            'Fluxio Demo',
            sprintf('Comprovativo de pagamento de %s', $number),
            sprintf('Fornecedor: %s', $supplierName),
            'Pagamento marcado como regularizado em ambiente de demonstracao.',
        ]);

        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
