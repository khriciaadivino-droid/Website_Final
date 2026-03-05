<?php

namespace App\Tests\Controller;

use App\Entity\Productss;
use App\Repository\ProductssRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductssControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $productssRepository;
    private string $path = '/productss/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->productssRepository = $this->manager->getRepository(Productss::class);

        foreach ($this->productssRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Productss index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'productss[name]' => 'Testing',
            'productss[description]' => 'Testing',
            'productss[price]' => 'Testing',
            'productss[category]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->productssRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Productss();
        $fixture->setName('My Title');
        $fixture->setDescription('My Title');
        $fixture->setPrice('My Title');
        $fixture->setCategory('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Productss');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Productss();
        $fixture->setName('Value');
        $fixture->setDescription('Value');
        $fixture->setPrice('Value');
        $fixture->setCategory('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'productss[name]' => 'Something New',
            'productss[description]' => 'Something New',
            'productss[price]' => 'Something New',
            'productss[category]' => 'Something New',
        ]);

        self::assertResponseRedirects('/productss/');

        $fixture = $this->productssRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getPrice());
        self::assertSame('Something New', $fixture[0]->getCategory());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Productss();
        $fixture->setName('Value');
        $fixture->setDescription('Value');
        $fixture->setPrice('Value');
        $fixture->setCategory('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/productss/');
        self::assertSame(0, $this->productssRepository->count([]));
    }
}
