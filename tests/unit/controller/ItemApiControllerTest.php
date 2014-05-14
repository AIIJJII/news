<?php
/**
 * ownCloud - News
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alessandro Cosentino <cosenal@gmail.com>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Alessandro Cosentino 2012
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\News\Controller;

use \OCP\AppFramework\Http;

use \OCA\News\BusinessLayer\BusinessLayerException;
use \OCA\News\Db\Item;

require_once(__DIR__ . "/../../classloader.php");


class ItemApiControllerTest extends \PHPUnit_Framework_TestCase {

	private $itemBusinessLayer;
	private $itemAPI;
	private $api;
	private $user;
	private $request;
	private $msg;

	protected function setUp() {
		$this->user = 'tom';
		$this->appName = 'news';
		$this->request = $this->getMockBuilder(
			'\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->itemBusinessLayer = $this->getMockBuilder(
			'\OCA\News\BusinessLayer\ItemBusinessLayer')
			->disableOriginalConstructor()
			->getMock();
		$this->itemAPI = new ItemApiController(
			$this->appName,
			$this->request,
			$this->itemBusinessLayer,
			$this->user
		);
		$this->msg = 'hi';
	}


	public function testIndex() {
		$items = [new Item()];

		$this->itemBusinessLayer->expects($this->once())
			->method('findAll')
			->with(
				$this->equalTo(2),
				$this->equalTo(1),
				$this->equalTo(30),
				$this->equalTo(20),
				$this->equalTo(false),
				$this->equalTo($this->user)
			)
			->will($this->returnValue($items));

		$response = $this->itemAPI->index(1, 2, false, 30, 20);

		$this->assertEquals($items, $response);
	}


	public function testIndexDefaultBatchSize() {
		$items = [new Item()];

		$this->itemBusinessLayer->expects($this->once())
			->method('findAll')
			->with(
				$this->equalTo(2),
				$this->equalTo(1),
				$this->equalTo(20),
				$this->equalTo(0),
				$this->equalTo(false),
				$this->equalTo($this->user)
			)
			->will($this->returnValue($items));

		$response = $this->itemAPI->index(1, 2, false);

		$this->assertEquals($items, $response);
	}


	public function testUpdated() {
		$items = [new Item()];

		$this->itemBusinessLayer->expects($this->once())
			->method('findAllNew')
			->with(
				$this->equalTo(2),
				$this->equalTo(1),
				$this->equalTo(30),
				$this->equalTo(true),
				$this->equalTo($this->user)
			)
			->will($this->returnValue($items));

		$response = $this->itemAPI->updated(1, 2, 30);

		$this->assertEquals($items, $response);
	}


	public function testRead() {
		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->with(
				$this->equalTo(2),
				$this->equalTo(true),
				$this->equalTo($this->user)
			);

		$this->itemAPI->read(2);
	}


	public function testReadDoesNotExist() {
		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->will($this->throwException(new BusinessLayerException($this->msg)));

		$response = $this->itemAPI->read(2);

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}


	public function testUnread() {
		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->with(
				$this->equalTo(2),
				$this->equalTo(false),
				$this->equalTo($this->user)
			);

		$this->itemAPI->unread(2);
	}


	public function testUnreadDoesNotExist() {
		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->will($this->throwException(new BusinessLayerException($this->msg)));

		$response = $this->itemAPI->unread(2);

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}


	public function testStar() {
		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->with(
				$this->equalTo(2),
				$this->equalTo('hash'),
				$this->equalTo(true),
				$this->equalTo($this->user)
			);

		$this->itemAPI->star(2, 'hash');
	}


	public function testStarDoesNotExist() {
		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->will($this->throwException(new BusinessLayerException($this->msg)));

		$response = $this->itemAPI->star(2, 'test');

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}


	public function testUnstar() {
		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->with(
				$this->equalTo(2),
				$this->equalTo('hash'),
				$this->equalTo(false),
				$this->equalTo($this->user)
			);

		$this->itemAPI->unstar(2, 'hash');
	}


	public function testUnstarDoesNotExist() {
		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->will($this->throwException(new BusinessLayerException($this->msg)));

		$response = $this->itemAPI->unstar(2, 'test');

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}


	public function testReadAll() {
		$this->itemBusinessLayer->expects($this->once())
			->method('readAll')
			->with(
				$this->equalTo(30),
				$this->equalTo($this->user));

		$this->itemAPI->readAll(30);
	}



	public function testReadMultiple() {
		$this->itemBusinessLayer->expects($this->at(0))
			->method('read')
			->with($this->equalTo(2),
				$this->equalTo(true),
				$this->equalTo($this->user));
		$this->itemBusinessLayer->expects($this->at(1))
			->method('read')
			->with($this->equalTo(4),
				$this->equalTo(true),
				$this->equalTo($this->user));
		$this->itemAPI->readMultiple([2, 4]);
	}


	public function testReadMultipleDoesntCareAboutException() {
		$this->itemBusinessLayer->expects($this->at(0))
			->method('read')
			->will($this->throwException(new BusinessLayerException('')));
		$this->itemBusinessLayer->expects($this->at(1))
			->method('read')
			->with($this->equalTo(4),
				$this->equalTo(true),
				$this->equalTo($this->user));
		$this->itemAPI->readMultiple([2, 4]);
	}


	public function testUnreadMultiple() {
		$this->itemBusinessLayer->expects($this->at(0))
			->method('read')
			->with($this->equalTo(2),
				$this->equalTo(false),
				$this->equalTo($this->user));
		$this->itemBusinessLayer->expects($this->at(1))
			->method('read')
			->with($this->equalTo(4),
				$this->equalTo(false),
				$this->equalTo($this->user));
		$this->itemAPI->unreadMultiple([2, 4]);
	}


	public function testStarMultiple() {
		$ids = [
					[
						'feedId' => 2,
						'guidHash' => 'a'
					],
					[
						'feedId' => 4,
						'guidHash' => 'b'
					]
				];

		$this->itemBusinessLayer->expects($this->at(0))
			->method('star')
			->with($this->equalTo(2),
				$this->equalTo('a'),
				$this->equalTo(true),
				$this->equalTo($this->user));
		$this->itemBusinessLayer->expects($this->at(1))
			->method('star')
			->with($this->equalTo(4),
				$this->equalTo('b'),
				$this->equalTo(true),
				$this->equalTo($this->user));
		$this->itemAPI->starMultiple($ids);
	}


	public function testStarMultipleDoesntCareAboutException() {
		$ids = [
					[
						'feedId' => 2,
						'guidHash' => 'a'
					],
					[
						'feedId' => 4,
						'guidHash' => 'b'
					]
				];

		$this->itemBusinessLayer->expects($this->at(0))
			->method('star')
			->will($this->throwException(new BusinessLayerException('')));
		$this->itemBusinessLayer->expects($this->at(1))
			->method('star')
			->with($this->equalTo(4),
				$this->equalTo('b'),
				$this->equalTo(true),
				$this->equalTo($this->user));
		$this->itemAPI->starMultiple($ids);
	}


	public function testUnstarMultiple() {
		$ids = [
					[
						'feedId' => 2,
						'guidHash' => 'a'
					],
					[
						'feedId' => 4,
						'guidHash' => 'b'
					]
				];

		$this->itemBusinessLayer->expects($this->at(0))
			->method('star')
			->with($this->equalTo(2),
				$this->equalTo('a'),
				$this->equalTo(false),
				$this->equalTo($this->user));
		$this->itemBusinessLayer->expects($this->at(1))
			->method('star')
			->with($this->equalTo(4),
				$this->equalTo('b'),
				$this->equalTo(false),
				$this->equalTo($this->user));
		$this->itemAPI->unstarMultiple($ids);
	}


}
