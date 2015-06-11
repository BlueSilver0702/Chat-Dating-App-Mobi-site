<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use ChatApp\Util\Inflector;

class FileController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->get('/image', 'ChatApp\Controller\Api\FileController::drawImageAction');
        $index->get('/image/{filename}', 'ChatApp\Controller\Api\FileController::drawImageAction');
        $index->get('/get_file_url/{filename}', 'ChatApp\Controller\Api\FileController::getFileUrlAction');
        $index->post('/upload_file', 'ChatApp\Controller\Api\FileController::uploadFileAction');

        return $index;
    }

    /**
     * Returns image content.
     *
     * @param string   $filename An image filename
     * @param interger $width    The image width
     * @param interger $height   The image height
     *
     * @return Response The image content
     *
     * @Route("/api/image")
     * @Route("/api/image/{filename}")
     * @method("GET")
     */
    public function drawImageAction(Application $app, Request $request)
    {
        $filename = $request->get('filename');

        if (!file_exists($file = $app['media_dir'].$filename)) {
            throw new FileNotFoundException(sprintf('Image "%s" does not exist.', $filename));
        }

        $image = $app['imagine']->open($file);

        // resize image
        if ($request->get('width') || $request->get('height')) {
            $transformation = new \Imagine\Filter\Transformation();
            $transformation->thumbnail(new \Imagine\Image\Box($request->get('width'), $request->get('height')));
            $image = $transformation->apply($image);
        }

        $format = pathinfo($file, PATHINFO_EXTENSION);

        $response = new Response();
        $response->headers->set('Content-type', 'image/'.$format);
        $response->setContent($image->get($format));

        return $response;
    }

    /**
     * Returns file url path.
     *
     * @param string $filename An image filename
     *
     * @return Response The file url
     *
     * @Route("/api/get_file_url/{filename}")
     * @method("GET")
     */
    public function getFileUrlAction(Application $app, Request $request)
    {
        $filename = $request->get('filename');

        if (!$filename || !file_exists($app['media_dir'].$filename)) {
            throw new FileNotFoundException('Missing file.');
        }

        return new Response($app['media_url'].$filename);
    }

    /**
     * Upload file to server.
     *
     * @param UploadedFile $file An uploaded file
     *
     * @return Response The filename
     *
     * @Route("/api/upload_file")
     * @method("POSt")
     */
    public function uploadFileAction(Application $app, Request $request)
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new UploadException('Missing file.');
        }

        $fileConst = new \Symfony\Component\Validator\Constraints\File(array(
            'maxSize'   => '5120k',
            'mimeTypes' => array(
                'image/bmp', 'image/gif', 'image/jpeg', 'image/png',
                'audio/mpeg', 'audio/3gpp', 'audio/3gpp2', 'audio/mp4',
                'video/mpeg', 'video/3gpp', 'video/3gpp2', 'video/mp4',
            ),
        ));

        $errors = $app['validator']->validateValue($file, $fileConst);
        if (count($errors) > 0) {
            throw new UnexpectedTypeException('Invalid file.');
        }

        $filename = Inflector::getRandomString(32).'.'.$file->guessExtension();
        $file->move($app['media_dir'], $filename);

        return new Response($filename);
    }
}
