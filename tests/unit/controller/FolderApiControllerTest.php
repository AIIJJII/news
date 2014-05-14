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
use \OCP\AppFramework\Http\JSONResponse;

use \OCA\News\BusinessLayer\BusinessLayerException;
use \OCA\News\BusinessLayer\BusinessLayerConflictException;
use \OCA\News\BusinessLayer\BusinessLayerValidationException;

use \OCA\News\Db\Folder;
use \OCA\News\Db\Feed;
use \OCA\News\Db\Item;

require_once(__DIR__ . "/../../classloader.php");


class FolderApiControllerTest extends \PHPUnit_Framework_TestCase {

	private $folderBusinessLayer;
	private $itemBusinessLayer;
	private $folderAPI;
	private $appName;
	private $user;
	private $request;
	private $msg;

	protected function setUp() {
		$this->appName = 'news';
		$this->user = 'tom';
		$this->request = $this->getMockBuilder(
			'\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->folderBusinessLayer = $this->getMockBuilder(
			'\OCA\News\BusinessLayer\FolderBusinessLayer')
			->disableOriginalConstructor()
			->getMock();
		$this->itemBusinessLayer = $this->getMockBuilder(
			'\OCA\News\BusinessLayer\ItemBusinessLayer')
			->disableOriginalConstructor()
			->getMock();
		$this->folderAPI = new FolderApiController(
			$this->appName,
			$this->request,
			$this->folderBusinessLayer,
			$this->itemBusinessLayer,
			$this->user
		);
		$this->msg = 'test';
	}


	public function testIndex() {
		$folders = [new Folder()];

		$this->folderBusinessLayer->expects($this->once())
			->method('findAll')
			->with($this->equalTo($this->user))
			->will($this->returnValue($folders));

		$response = $this->folderAPI->index();

		$this->assertEquals($folders, $response);
	}


	public function testCreate() {
		$folderName = 'test';
		$folder = new Folder();
		$folder->setName($folderName);
		$folders = [$folder];
		
		$this->folderBusinessLayer->expects($this->once())
			->method('purgeDeleted')
			->with($this->equalTo($this->user), $this->equalTo(false));
		$this->folderBusinessLayer->expects($this->once())
			->method('create')
			->with($this->equalTo($folderName), $this->equalTo($this->user))
			->will($this->returnValue($folder));

		$response = $this->folderAPI->create($folderName);

		$this->assertEquals($folder, $response);
	}


	public function testCreateAlreadyExists() {
		$msg = 'exists';

		$this->folderBusinessLayer->expects($this->once())
			->method('purgeDeleted')
			->with($this->equalTo($this->user), $this->equalTo(false));
		$this->folderBusinessLayer->expects($this->once())
			->method('create')
			->will($this->throwException(new BusinessLayerConflictException($msg)));

		$response = $this->folderAPI->create('hi');

		$data = $response->getData();
		$this->assertEquals($msg, $data['message']);
		$this->assertEquals(Http::STATUS_CONFLICT, $response->getStatus());
	}


	public function testCreateInvalidFolderName() {
		$msg = 'exists';

		$this->folderBusinessLayer->expects($this->once())
			->method('purgeDeleted')
			->with($this->equalTo($this->user), $this->equalTo(false));
		$this->folderBusinessLayer->expects($this->once())
			->method('create')
			->will($this->throwException(new BusinessLayerValidationException($msg)));

		$response = $this->folderAPI->create('hi');

		$data = $response->getData();
		$this->assertEquals($msg, $data['message']);
		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}


	public function testDelete() {
		$folderId = 23;
		$this->folderBusinessLayer->expects($this->once())
			->method('delete')
			->with($this->equalTo($folderId), $this->equalTo($this->user));

		$response = $this->folderAPI->delete(23);
	}


	public function testDeleteDoesNotExist() {
		$folderId = 23;

		$this->folderBusinessLayer->expects($this->once())
			->method('delete')
			->will($this->throwException(new BusinessLayerException($this->msg)));

		$response = $this->folderAPI->delete($folderId);

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}


	public function testUpdate() {
		$folderId = 23;
		$folderName = 'test';

		$this->folderBusinessLayer->expects($this->once())
			->method('rename')
			->with($this->equalTo($folderId),
				$this->equalTo($folderName),
				$this->equalTo($this->user));

		$this->folderAPI->update($folderId, $folderName);
	}

	public function testUpdateDoesNotExist() {
		$folderId = 23;
		$folderName = 'test';

		$this->folderBusinessLayer->expects($this->once())
			->method('rename')
			->will($this->throwException(new BusinessLayerException($this->msg)));

		$response = $this->folderAPI->update($folderId, $folderName);

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}


	public function testUpdateExists() {
		$folderId = 23;
		$folderName = 'test';

		$this->folderBusinessLayer->expects($this->once())
			->method('rename')
			->will($this->throwException(new BusinessLayerConflictException($this->msg)));

		$response = $this->folderAPI->update($folderId, $folderName);

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_CONFLICT, $response->getStatus());
	}


	public function testUpdateInvalidFolderName() {
		$folderId = 23;
		$folderName = '';

		$this->folderBusinessLayer->expects($this->once())
			->method('rename')
			->will($this->throwException(new BusinessLayerValidationException($this->msg)));

		$response = $this->folderAPI->update($folderId, $folderName);

		$data = $response->getData();
		$this->assertEquals($this->msg, $data['message']);
		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}


	public function testRead() {
		$this->itemBusinessLayer->expects($this->once())
			->method('readFolder')
			->with(
				$this->equalTo(3),
				$this->equalTo(30),
				$this->equalTo($this->user));

		$this->folderAPI->read(3, 30);
	}


}
