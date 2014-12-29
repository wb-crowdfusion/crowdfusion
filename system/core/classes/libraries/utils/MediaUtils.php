<?php
/**
 * MediaUtils
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2010 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009-2010 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: MediaUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * MediaUtils
 *
 * @package     CrowdFusion
 */
class MediaUtils {

//	protected $thumbnailAPI, $storageFacilityService, $mediaService;
/*
	public function processMediaToStorageFacility(Media $media, $fileid = null, $storageFacilityId = null) {

		$existingMedia = null;

		if($media->getID() != null) {
			$existingMedia = $this->mediaService->getByID($media->getID());
			if(!empty($existingMedia))
				$existingStorageFacility = $this->storageFacilityService->getStorageFacilityByID($existingMedia->StorageFacilityID);
		}

		$errors = new Errors();
		$errors->rejectIfInvalid(  'media.Slug', 'field', 'Slug', $media->Slug, new ValidationExpression($media->getValidationExpression('Slug')))->throwOnError();

		$site = $media->getSite();
		$mediaType = $media->getType();

		$canAlterStoredFile = (empty($existingMedia)
				 || (!defined('MEDIA_TESTING') || MEDIA_TESTING == false) && $media->TestingOnly != true)
				 || (defined('MEDIA_TESTING') && MEDIA_TESTING == true && $existingMedia->TestingOnly == true);

		if($canAlterStoredFile) {
			// get the new storage facility; if not overridden, use default from MediaType
			if($storageFacilityId !== null) {
				$newStorageFacility = $this->storageFacilityService->getStorageFacilityByID($storageFacilityId);
			} else if (!empty($existingStorageFacility)){
				$newStorageFacility = $existingStorageFacility;
			} else {
				$newStorageFacility = $this->storageFacilityService->getStorageFacilityByID($mediaType->StorageFacilityID);
			}



			// changing the file
			if(!empty($fileid)) {

				$originalFile = $this->uploadService->getFileByID($site, $fileid);

				// save a unique filename as the Slug of the media record
		        $media->Slug = $this->findUniqueFilenameOnStorageFacility($site, $newStorageFacility, $media->Slug, $originalFile->getExtension());

		        if($this->mediaService->slugExists($media->Slug, $media->SiteID, $mediaType->getTypeID(), $media->MediaID )  ) {
		        	throw new ValidationException('slug.exists', 'Slug', 'Media record with Slug ['.$media->Slug.'] already exists.');
		        }

				if(!empty($existingMedia))
					$this->deleteMediaFromStorageFacility($existingMedia);

				$media = $this->addMediaToStorageFacility($media, $newStorageFacility, $originalFile);

//				$this->uploadService->deleteFile($site, $fileid);

			} else if (!empty($existingMedia)){




				// changing storage facilities
				if ($existingStorageFacility->getStorageFacilityID() != $newStorageFacility->getStorageFacilityID()) {
					// save a unique filename as the Slug of the media record
			        $media->Slug = $this->findUniqueFilenameOnStorageFacility($site, $newStorageFacility, $media->Slug, $media->getExtension());

			        if($this->mediaService->slugExists($media->Slug, $media->SiteID, $mediaType->getTypeID(), $media->MediaID )  ) {
			        	throw new ValidationException('slug.exists', 'Slug', 'Media record with Slug ['.$media->Slug.'] already exists.');
			        }

					$media = $this->moveMediaToNewStorageFacility($existingMedia, $media, $existingStorageFacility, $newStorageFacility);

				// changing slugs
				} else if($existingMedia->Slug != $media->Slug) {

					// save a unique filename as the Slug of the media record
			        $media->Slug = $this->findUniqueFilenameOnStorageFacility($site, $newStorageFacility, $media->Slug, $media->getExtension());

			        if($this->mediaService->slugExists($media->Slug, $media->SiteID, $mediaType->getTypeID(), $media->MediaID )  ) {
			        	throw new ValidationException('slug.exists', 'Slug', 'Media record with Slug ['.$media->Slug.'] already exists.');
			        }

					$media = $this->renameMediaOnStorageFacility($existingMedia, $media);

				}


			}
		}

		return $media;
	}


	public function deleteMediaFromStorageFacility(Media $existingMedia) {

		$site = $existingMedia->getSite();
		$mediaType = $existingMedia->getType();
		$existingStorageFacility = $this->storageFacilityService->getStorageFacilityByID($existingMedia->StorageFacilityID);
		$sizes = $this->_getThumbnailSizes($mediaType->Directories);

		if(!empty($existingMedia->StorageFacilityFileSlug)) {

			$existingFile = $existingStorageFacility->getFile($site, $existingMedia->StorageFacilityFileSlug);

			// delete thumbnails from storage facility
			$path = pathinfo($existingFile->getId());

			foreach ( $sizes as $size) {

				$newid = $path['dirname']."/${size}/".$path['basename'];

				try {
					$existingStorageFacility->deleteFile($site, $newid);
				} catch (StorageFacilityException $sfe) {}
			}

			// delete file from storage facility
			$existingStorageFacility->deleteFile($site, $existingFile->getId());

			@unlink($existingFile->getLocalPath());
		}
	}

	private function findUniqueFilenameOnStorageFacility(Sites $site, StorageFacility $newStorageFacility, $slug, $extension) {

        // file a unique file name on the new storage facility
        $uniqueFilename = $newStorageFacility->findUniqueFileID($site, "/{$slug}.{$extension}");

        // save that unique filename as the Slug of the media record
        return trim(substr($uniqueFilename, 0, strrpos($uniqueFilename, '.')), '/');

	}

	private function addMediaToStorageFacility(Media $media, StorageFacility $newStorageFacility, $newFile) {

		$site = $media->getSite();
		$mediaType = $media->getType();
		$sizes = $this->_getThumbnailSizes($mediaType->Directories);

        // set the new filename on the originalFile, keeping the old localPath
        $newFile->setId("/{$media->Slug}.{$newFile->getExtension()}");

        // create thumbnails (except for pdf)
        if($newFile->getExtension() !== 'pdf') {
            $thumbs = $this->_generateThumbnails($newFile,$sizes);

            // store thumbnails
            foreach($thumbs as $thumb) {
                $newStorageFacility->putFile($site,$thumb);
                @unlink($thumb->getLocalPath());
            }
        }

        // store the file
        $newStorageFacility->putFile($site,$newFile);

        // save the media record
        $media->StorageFacilityID = $newStorageFacility->getStorageFacilityID();
        $media->StorageFacilityFileSlug = $newFile->getId();
        $media->MimeType = get_mimetype($newFile->getExtension());
        $media->URL = $newFile->getUrl();
        $media->Extension = $newFile->getExtension();
        $media->Filesize = filesize($newFile->getLocalPath());
		$media->ModifiedDate = new StorageDate();
        list($media->Width, $media->Height) = get_image_dimensions($newFile->getLocalPath());

		return $media;
	}

	private function moveMediaToNewStorageFacility(Media $existingMedia, Media $media, $newStorageFacility) {


		$site = $media->getSite();
		$mediaType = $media->getType();
		$sizes = $this->_getThumbnailSizes($mediaType->Directories);
		$existingStorageFacility = $this->storageFacilityService->getStorageFacilityByID($existingMedia->StorageFacilityID);

		$existingFile = $existingStorageFacility->getFile($site, $existingMedia->StorageFacilityFileSlug);

		$newfileid = "/{$media->Slug}.{$media->Extension}";

		$oldpath = pathinfo($existingFile->getId());
		$path = pathinfo($newfileid);

		foreach ( $sizes as $size) {

			$oldid = $oldpath['dirname']."/${size}/".$oldpath['basename'];
			$newid = $path['dirname']."/${size}/".$path['basename'];

			try {
				$existingThumb = $existingStorageFacility->getFile($site, $oldid);
				$existingThumb->setId($newid);
				$newStorageFacility->putFile($site, $existingThumb);
				$existingStorageFacility->deleteFile($site, $oldid);
				@unlink($existingThumb->getLocalPath());
			} catch(StorageFacilityException $sfe) {}
		}

		$newFile = $newStorageFacility->putFile($site, $existingFile);
		$existingStorageFacility->deleteFile($site, $existingFile->getId());

		@unlink($existingFile->getLocalPath());


		$media->StorageFacilityID = $newStorageFacility->getStorageFacilityID();
		$media->StorageFacilityFileSlug = $newFile->getId();
		$media->URL = $newFile->getUrl();

		return $media;
	}

	private function renameMediaOnStorageFacility(Media $existingMedia, Media $media) {

		$site = $media->getSite();
		$mediaType = $media->getType();
		$sizes = $this->_getThumbnailSizes($mediaType->Directories);
		$existingStorageFacility = $this->storageFacilityService->getStorageFacilityByID($existingMedia->StorageFacilityID);

		$newfileid = "/{$media->Slug}.{$existingMedia->Extension}";

		$existingStorageFacility->renameFile($site, $newFile = new File($existingMedia->StorageFacilityFileSlug),  $newfileid);

		$media->StorageFacilityFileSlug = $newFile->getId();
		$media->URL = $newFile->getUrl();

		// rename thumbnail files on storage facility
		$oldpath = pathinfo($existingMedia->StorageFacilityFileSlug);
		$path = pathinfo($newfileid);

		$sizes = $this->_getThumbnailSizes($mediaType->Directories);

		foreach ( $sizes as $size) {
			$oldid = $oldpath['dirname']."/${size}/".$oldpath['basename'];
			$newid = $path['dirname']."/${size}/".$path['basename'];
			$existingStorageFacility->renameFile($site, new File($oldid), $newid);
		}

		return $media;
	}

	protected function _getThumbnailSizes($directories) {

		$sizes = explode(",",$directories);
		$sizes[] = MEDIA_CMS_THUMBNAIL_SIZE;

		return $sizes;
	}
*/
	/**
	 * Local path in $file must be a valid file
	 *
	 */
/*
	protected function _generateThumbnails(File $file,array $sizes) {

		if(!is_file($file->getLocalPath()))
			throw new Exception("invalid file [generateThumbnails]: ".$file->getLocalPath());

		$thumbnails = array();

		foreach($sizes as $size) {

			$thumbFilename = $this->thumbnailAPI->generateThumbnail($file->getLocalPath(),$size);

			if($thumbFilename !== null) {
				$path = pathinfo($file->getId());

				$newid = $path['dirname']."/${size}/".$path['basename'];

				$path = pathinfo($file->getLocalPath());

				$thumbnails[] = new File($newid,$path['dirname'].'/'.$thumbFilename);
			}
		}

		return $thumbnails;
	}

	public function regenerateThumbnails() {

		$sql = "SELECT
					MediaID,
					SiteID,
					URL,
					TestingOnly,
					Slug,
			 		Status,
					MediaTypeID,
					StorageFacilityID,
					StorageFacilityFileSlug
					FROM media ORDER BY mediaid DESC";

		$allmedia = $this->db->readAll($sql, false);
		$count = 0;
		foreach($allmedia as $mediaarr) {
			$media = new Media($mediaarr);

			$site = $media->getSite();
			$mediaType = $media->getType();
			$existingStorageFacility = $this->storageFacilityService->getStorageFacilityByID($media->StorageFacilityID);

			$canAlterStoredFile = ((!defined('MEDIA_TESTING') || MEDIA_TESTING == false) && $media->TestingOnly != true)
					 || (defined('MEDIA_TESTING') && MEDIA_TESTING == true && $media->TestingOnly == true);

			if($canAlterStoredFile) {

				$existingFile = $existingStorageFacility->getFile($site, $media->StorageFacilityFileSlug);

				$path = pathinfo($existingFile->getId());

				$sizes = $this->_getThumbnailSizes($mediaType->Directories);

				// remove existing sizes
				foreach ( $sizes as $k => $size) {

					$oldid = $path['dirname']."/${size}/".$path['basename'];

					try {
						$existingThumb = $existingStorageFacility->getFile($site, $oldid);
						@unlink($existingThumb->getLocalPath());
						unset($sizes[$k]);
					}catch (StorageFacilityException $sfe) {}

				}

				// create thumbnails
				$thumbs = $this->_generateThumbnails($existingFile,$sizes);

				// store thumbnails
				foreach($thumbs as $thumb) {
					++$count;
					$existingStorageFacility->putFile($site,$thumb);
					@unlink($thumb->getLocalPath());
				}

				@unlink($existingFile->getLocalPath());

			}
		}
		echo "Regenerated ".$count." thumbnails for ".count($allmedia)." media records\n";
	}
*/
}