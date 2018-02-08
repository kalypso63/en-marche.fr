<?php

namespace Tests\AppBundle\Admin;

use AppBundle\DataFixtures\ORM\LoadAdminData;
use AppBundle\DataFixtures\ORM\LoadArticleData;
use AppBundle\Entity\Article;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\MysqlWebTestCase;

/**
 * @group functional
 */
class ArticleAdminTest extends MysqlWebTestCase
{
    use ControllerTestTrait;

    public function testCreateCategoryFail(): void
    {
        $this->authenticateAsAdmin($this->client, 'superadmin@en-marche-dev.fr', 'superadmin');

        $crawler = $this->client->request(Request::METHOD_GET, '/admin/app/article/create');
        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        $form = $crawler->selectButton('btn_create_and_edit')->form();
        $this->client->submit($form);

        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        $this->assertValidationErrors(
            [
                'data.title',
                'data.description',
                'data.content',
                'data.media',
                'data.slug',
            ],
            $this->client->getContainer()
        );
    }

    public function testEditSlugToTriggerRedirectionListener(): void
    {
        $this->authenticateAsAdmin($this->client, 'superadmin@en-marche-dev.fr', 'superadmin');
        $ampClient = $this->makeClient(false, ['HTTP_HOST' => $this->hosts['amp']]);

        /** @var Article $article */
        $article = $this->manager->getRepository(Article::class)->findOneBySlug('outre-mer');

        $this->client->request(
            Request::METHOD_GET,
            sprintf('/articles/%s/%s', $article->getCategory()->getSlug(), $article->getSlug())
        );

        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $ampClient->request(
            Request::METHOD_GET,
            sprintf('/articles/%s/%s', $article->getCategory()->getSlug(), $article->getSlug())
        );

        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            sprintf('/admin/app/article/%s/edit', $article->getId())
        );
        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        $form = $crawler->selectButton('btn_update_and_edit')->form();

        $prefix = $this->getFirstPrefixForm($form);
        $form->setValues([
            $prefix.'[slug]' => 'outre-mer-new',
            $prefix.'[description]' => 'Vous devez saisir au moins 10 caractères.',
        ]);
        $this->client->submit($form);

        $this->assertStatusCode(Response::HTTP_FOUND, $this->client);

        $this->client->request(
            Request::METHOD_GET,
            sprintf('/articles/%s/%s', $article->getCategory()->getSlug(), $article->getSlug())
        );

        $this->assertClientIsRedirectedTo('/articles/actualites/outre-mer-new', $this->client, false, true);

        $ampClient->request(
            Request::METHOD_GET,
            sprintf('/articles/%s/%s', $article->getCategory()->getSlug(), $article->getSlug())
        );

        $this->assertClientIsRedirectedTo('/articles/actualites/outre-mer-new', $this->client, false, true);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadAdminData::class,
            LoadArticleData::class,
        ]);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->kill();
    }
}
