<?php


namespace ChrisComposer\Alibaba\OSS;

use ChrisComposer\Alibaba\OSS\Models\OssUrl;
use OSS\OssClient;
use OSS\Core\OssException;
use OSS\Core\OssUtil;

class OSS_Server
{
    public $bucket; // 上传到的 OSS 根目录
    public $accessKeyId;
    public $accessKeySecret;
    public $endpoint;
    public $is_duplicate_upload;
    public $partSize = 10 * 1024 * 1024; // 分片大小

    public function __construct(array $config = [])
    {
        $this->accessKeyId = $config['accessKeyId'] ?? config('alibaba_oss.accessKeyId');
        $this->accessKeySecret = $config['accessKeySecret'] ?? config('alibaba_oss.accessKeySecret');
        $this->endpoint = $config['endpoint'] ?? config('alibaba_oss.endpoint');
        $this->bucket = $config['bucket'] ?? config('alibaba_oss.bucket');
        $this->is_duplicate_upload = $config['is_duplicate_upload'] ?? config('alibaba_oss.is_duplicate_upload');
    }

    public function create_oss_url($md5_file, $oss_url)
    {
        $params['md5_file'] = $md5_file;
        $params['oss_url'] = $oss_url;

        OssUrl::create($params);
    }

    /**
     * 简单上传
     *
     * @param      $object     上传后的文件位置
     * @param      $uploadFile 上传的文件
     * @param      $file_url   上传后的返回的文件链接
     * @param null $bucket     上传到的 OSS 根目录
     *
     * @return array
     */
    public function simple_upload($object, $uploadFile, &$file_url, $bucket = null)
    {
        $bucket = $bucket ? $bucket : $this->bucket;

        # 是否去重上传，若是，则对比 md5 散列
        if ($this->is_duplicate_upload) {
            ## 对比 md5 散列，若此文件已经存在于 oss 上，则直接从数据库中返回链接
            $md5_file = md5_file($uploadFile);
            $res_md5 = OssUrl::where('md5_file', $md5_file)->first();
            if ($res_md5) {
                $res['file_url'] = $res_md5->oss_url;
                $file_url = $res_md5->oss_url;

                return ['code' => 200, 'data' => $res]; // 获取文件 url
            }
        }

        try {
            # 连接到 oss
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);

            $res = $ossClient->uploadFile($bucket, $object, $uploadFile);

            # create 表 data_oss_url 字段 md5 散列，对应的 oss-url 记录
            if ($this->is_duplicate_upload) {
                $this->create_oss_url(md5_file($uploadFile), $res['oss-request-url']);
            }

            # update file_url
            $file_url = $res['oss-request-url'];

            # add 参数 file_url
            $res['file_url'] = $res['oss-request-url'];

            return ['code' => 200, 'data' => $res];
        } catch (OssException $e) {
            return ['code' => $e->getHTTPStatus(), 'message' => $e->getErrorMessage()];
        }
    }

    /**
     * 分片上传
     *
     * @param      $object     上传后的文件位置
     * @param      $uploadFile 上传的文件
     * @param      $file_url   上传后的返回的文件链接
     * @param null $bucket     上传到的 OSS 根目录
     *
     * @return array
     */
    public function multipart_upload($object, $uploadFile, &$file_url, $bucket = null)
    {
        $bucket = $bucket ? $bucket : $this->bucket;

        # 是否去重上传，若是，则对比 md5 散列
        if ($this->is_duplicate_upload) {
            ## 对比 md5 散列，若此文件已经存在于 oss 上，则直接从数据库中返回链接
            $md5_file = md5_file($uploadFile);
            $res_md5 = OssUrl::where('md5_file', $md5_file)->first();
            if ($res_md5) {
                $res['file_url'] = $res_md5->oss_url;
                $file_url = $res_md5->oss_url;

                return ['code' => 200, 'data' => $res]; // 获取文件 url
            }
        }

        /**
         *  步骤1：初始化一个分片上传事件，获取uploadId。
         */
        try {
            # 连接到 oss
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);

            // 返回uploadId，它是分片上传事件的唯一标识，您可以根据这个ID来发起相关的操作，如取消分片上传、查询分片上传等。
            $uploadId = $ossClient->initiateMultipartUpload($bucket, $object);
        } catch (OssException $e) {
            return ['code' => $e->getHTTPStatus(), 'message' => $e->getErrorMessage()];
        }

        /*
         * 步骤2：上传分片。
         */
        $partSize = $this->partSize; // 分片大小
        $uploadFileSize = filesize($uploadFile);
        $pieces = $ossClient->generateMultiuploadParts($uploadFileSize, $partSize); // 分片数
        $responseUploadPart = array();
        $uploadPosition = 0;
        $isCheckMd5 = true;

        foreach ($pieces as $i => $piece) {
            $fromPos = $uploadPosition + (integer)$piece[$ossClient::OSS_SEEK_TO];
            $toPos = (integer)$piece[$ossClient::OSS_LENGTH] + $fromPos - 1;

            $upOptions = array(
                $ossClient::OSS_FILE_UPLOAD => $uploadFile, // 上传文件位置
                $ossClient::OSS_PART_NUM => ($i + 1), // 分片号
                $ossClient::OSS_SEEK_TO => $fromPos, // 指定开始位置
                $ossClient::OSS_LENGTH => $toPos - $fromPos + 1, // 文件长度
//                $ossClient::OSS_PART_SIZE => $partSize, // 分片大小
                $ossClient::OSS_CHECK_MD5 => $isCheckMd5, // 是否开启MD5校验，true为开启
            );
            // MD5校验。
            if ($isCheckMd5) {
                $contentMd5 = OssUtil::getMd5SumForFile($uploadFile, $fromPos, $toPos);
                $upOptions[$ossClient::OSS_CONTENT_MD5] = $contentMd5;
            }

            try {
                // 保存上传分片后的响应
                $responseUploadPart[] = $ossClient->uploadPart($bucket, $object, $uploadId, $upOptions);
            } catch (OssException $e) {
                return ['code' => $e->getHTTPStatus(), 'message' => $e->getErrorMessage()];
            }
        }

        # 遍历响应
        // $uploadParts是由每个分片的ETag和分片号（PartNumber）组成的数组。
        $uploadParts = array();
        foreach ($responseUploadPart as $i => $eTag) {
            $uploadParts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $eTag,
            );
        }
        /**
         * 步骤3：完成上传。
         */
        try {
            // 在执行该操作时，需要提供所有有效的$uploadParts。OSS收到提交的$uploadParts后，会逐一验证每个分片的有效性。当所有的数据分片验证通过后，OSS将把这些分片组合成一个完整的文件。
            $res = $ossClient->completeMultipartUpload($bucket, $object, $uploadId, $uploadParts);

            # create 表 data_oss_url，字段 md5 散列和对应的 oss-url
            $endpoint = env('OSS_END_POINT');
            $oss_url = "http://{$bucket}.{$endpoint}/{$object}";

            if ($this->is_duplicate_upload) {
                $this->create_oss_url(md5_file($uploadFile), $oss_url);
            }

            # update $file_url
            $file_url = $oss_url;

            # add 参数 file_url
            $res['file_url'] = $oss_url;

            return ['code' => 200, 'data' => $res];
        } catch (OssException $e) {
            return ['code' => $e->getHTTPStatus(), 'message' => $e->getErrorMessage()];
        }
    }

    /**
     * 删除 oss 上内容
     *
     * @param      $object 文件的位置
     * @param null $bucket 文件的 OSS 根目录
     *
     * @return array
     */
    public function delete($object, $bucket = null)
    {
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);

            $ossClient->deleteObject($bucket ? $bucket : $this->bucket, $object);
        } catch (OssException $e) {
            return ['code' => $e->getHTTPStatus(), 'message' => $e->getErrorMessage()];
        }
    }
}