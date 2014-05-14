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

use \OCA\News\Db\Item;
use \OCA\News\Db\Feed;
use \OCA\News\Db\FeedType;
use \OCA\News\BusinessLayer\BusinessLayerException;

require_once(__DIR__ . "/../../classloader.php");


class ItemControllerTest extends \PHPUnit_Framework_TestCase {

	private $appName;
	private $settings;
	private $itemBusinessLayer;
	private $feedBusinessLayer;
	private $request;
	private $controller;
	private $newestItemId;


	/**
	 * Gets run before each test
	 */
	public function setUp(){
		$this->appName = 'news';
		$this->user = 'jackob';
		$this->settings = $this->getMockBuilder(
			'\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->itemBusinessLayer = 
		$this->getMockBuilder('\OCA\News\BusinessLayer\ItemBusinessLayer')
			->disableOriginalConstructor()
			->getMock();
		$this->feedBusinessLayer = 
		$this->getMockBuilder('\OCA\News\BusinessLayer\FeedBusinessLayer')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMockBuilder(
			'\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new ItemController($this->appName, $this->request,
				$this->feedBusinessLayer, $this->itemBusinessLayer, $this->settings,
				$this->user);
		$this->newestItemId = 12312;
	}


	public function testRead(){
		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->with(4, true, $this->user);

		$this->controller->read(4);
	}


	public function testReadDoesNotExist(){
		$msg = 'hi';

		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->will($this->throwException(new BusinessLayerException($msg)));

		$response = $this->controller->read(4);
		$params = json_decode($response->render(), true);

		$this->assertEquals($response->getStatus(), Http::STATUS_NOT_FOUND);
		$this->assertEquals($msg, $params['message']);
	}


	public function testUnread(){
		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->with(4, false, $this->user);

		$this->controller->unread(4);
	}



	public function testUnreadDoesNotExist(){
		$msg = 'hi';

		$this->itemBusinessLayer->expects($this->once())
			->method('read')
			->will($this->throwException(new BusinessLayerException($msg)));


		$response = $this->controller->unread(4);
		$params = json_decode($response->render(), true);

		$this->assertEquals($response->getStatus(), Http::STATUS_NOT_FOUND);
		$this->assertEquals($msg, $params['message']);
	}


	public function testStar(){
		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->with(
				$this->equalTo(4), 
				$this->equalTo('test'),
				$this->equalTo(true), 
				$this->equalTo($this->user));

		$this->controller->star(4, 'test');
	}


	public function testStarDoesNotExist(){
		$msg = 'ho';

		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->will($this->throwException(new BusinessLayerException($msg)));;

		$response = $this->controller->star(4, 'test');
		$params = json_decode($response->render(), true);

		$this->assertEquals($response->getStatus(), Http::STATUS_NOT_FOUND);
		$this->assertEquals($msg, $params['message']);
	}


	public function testUnstar(){
		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->with(
				$this->equalTo(4), 
				$this->equalTo('test'),
				$this->equalTo(false), 
				$this->equalTo($this->user));

		$this->controller->unstar(4, 'test');
	}


	public function testUnstarDoesNotExist(){
		$msg = 'ho';

		$this->itemBusinessLayer->expects($this->once())
			->method('star')
			->will($this->throwException(new BusinessLayerException($msg)));;

		$response = $this->controller->unstar(4, 'test');
		$params = json_decode($response->render(), true);

		$this->assertEquals($response->getStatus(), Http::STATUS_NOT_FOUND);
		$this->assertEquals($msg, $params['message']);
	}


	public function testReadAll(){
		$feed = new Feed();

		$expected = ['feeds' => [$feed]];

		$this->itemBusinessLayer->expects($this->once())
			->method('readAll')
			->with($this->equalTo(5), 
				$this->equalTo($this->user));
		$this->feedBusinessLayer->expects($this->once())
			->method('findAll')
			->with($this->equalTo($this->user))
			->will($this->returnValue([$feed]));

		$response = $this->controller->readAll(5);
		$this->assertEquals($expected, $response);
	}


	private function itemsApiExpects($id, $type){
		$this->settings->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->user),
				$this->equalTo($this->appName),
				$this->equalTo('showAll'))
			->will($this->returnValue('1'));
		$this->settings->expects($this->at(1))
			->method('setUserValue')
			->with($this->equalTo($this->user),
				$this->equalTo($this->appName),
				$this->equalTo('lastViewedFeedId'),
				$this->equalTo($id));
		$this->settings->expects($this->at(2))
			->method('setUserValue')
			->with($this->equalTo($this->user),
				$this->equalTo($this->appName),
				$this->equalTo('lastViewedFeedType'),
				$this->equalTo($type));
	}


	public function testIndex(){
		$feeds = [new Feed()];
		$result = [
			'items' => [new Item()],
			'feeds' => $feeds,
			'newestItemId' => $this->newestItemId,
			'starred' => 3111
		];

		$this->itemsApiExpects(2, FeedType::FEED);

		$this->feedBusinessLayer->expects($this->once())
			->method('findAll')
			->with($this->equalTo($this->user))
			->will($this->returnValue($feeds));

		$this->itemBusinessLayer->expects($this->once())
			->method('getNewestItemId')
			->with($this->equalTo($this->user))
			->will($this->returnValue($this->newestItemId));

		$this->itemBusinessLayer->expects($this->once())
			->method('starredCount')
			->with($this->equalTo($this->user))
			->will($this->returnValue(3111));

		$this->itemBusinessLayer->expects($this->once())
			->method('findAll')
			->with(
				$this->equalTo(2), 
				$this->equalTo(FeedType::FEED), 
				$this->equalTo(3), 
				$this->equalTo(0),
				$this->equalTo(true), 
				$this->equalTo($this->user))
			->will($this->returnValue($result['items']));

		$response = $this->controller->index(FeedType::FEED, 2, 3);
		$this->assertEquals($result, $response);
	}


	public function testItemsOffsetNotZero(){
		$result = ['items' => [new Item()]];

		$this->itemsApiExpects(2, FeedType::FEED);

		$this->itemBusinessLayer->expects($this->once())
			->method('findAll')
			->with($this->equalTo(2), 
				$this->equalTo(FeedType::FEED), 
				$this->equalTo(3), 
				$this->equalTo(10),
				$this->equalTo(true), 
				$this->equalTo($this->user))
			->will($this->returnValue($result['items']));

		$this->feedBusinessLayer->expects($this->never())
			->method('findAll');

		$response = $this->controller->index(FeedType::FEED, 2, 3, 10);
		$this->assertEquals($result, $response);
	}


	public function testGetItemsNoNewestItemsId(){
		$this->itemsApiExpects(2, FeedType::FEED);

		$this->itemBusinessLayer->expects($this->once())
			->method('getNewestItemId')
			->with($this->equalTo($this->user))
			->will($this->throwException(new BusinessLayerException('')));

		$response = $this->controller->index(FeedType::FEED, 2, 3);
		$this->assertEquals([], $response);
	}


	public function testNewItems(){
		$feeds = [new Feed()];
		$result = [
			'items' => [new Item()],
			'feeds' => $feeds,
			'newestItemId' => $this->newestItemId,
			'starred' => 3111
		];

		$this->settings->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->user),
				$this->equalTo($this->appName),
				$this->equalTo('showAll'))
			->will($this->returnValue('1'));

		$this->feedBusinessLayer->expects($this->once())
			->method('findAll')
			->with($this->equalTo($this->user))
			->will($this->returnValue($feeds));

		$this->itemBusinessLayer->expects($this->once())
			->method('getNewestItemId')
			->with($this->equalTo($this->user))
			->will($this->returnValue($this->newestItemId));

		$this->itemBusinessLayer->expects($this->once())
			->method('starredCount')
			->with($this->equalTo($this->user))
			->will($this->returnValue(3111));

		$this->itemBusinessLayer->expects($this->once())
			->method('findAllNew')
			->with(
				$this->equalTo(2), 
				$this->equalTo(FeedType::FEED), 
				$this->equalTo(3),
				$this->equalTo(true), 
				$this->equalTo($this->user))
			->will($this->returnValue($result['items']));

		$response = $this->controller->newItems(FeedType::FEED, 2, 3);
		$this->assertEquals($result, $response);
	}


	public function testGetNewItemsNoNewestItemsId(){
		$this->settings->expects($this->once())
			->method('getUserValue')
			->with($this->equalTo($this->user),
				$this->equalTo($this->appName),
				$this->equalTo('showAll'))
			->will($this->returnValue('1'));

		$this->itemBusinessLayer->expects($this->once())
			->method('getNewestItemId')
			->with($this->equalTo($this->user))
			->will($this->throwException(new BusinessLayerException('')));

		$response = $this->controller->newItems(FeedType::FEED, 2, 3);
		$this->assertEquals([], $response);
	}


}