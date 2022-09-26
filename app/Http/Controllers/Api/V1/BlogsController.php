<?php

namespace App\Http\Controllers\Api\V1;

use App\Blogs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\BaseController;

/**
 * Class BizVerificationController
 *
 * @package App\Http\Controllers\Api\V1
 */
class BlogsController extends BaseController
{

	/**
	 * BizVerificationController constructor.
	 */
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|Response
	 */
	public function get(Request $request)
    {

        $results=Blogs::all();
        return $this->respond($results);
        
    }

    public function post(Request $request)
    {

        return Blogs::create(
            $request->all()
        );
        
    }

    public function blogsById(Request $request)
    {

        $data=$request->all();
        $results=Blogs::where('id',$data[0])->get();
        return $this->respond($results);
        
    }
    
    public function update(Request $request)
    {
        $id = $request->id;
        $data = [
            'title'    => $request->title,
            'description'     => $request->description,
            'shortDesc'   => $request->shortDesc,
            'owner' => $request->owner,
            'date' => $request->date
        ];
        if(isset($request->image)){
            $data['image']    = $request->image;
        }
        return Blogs::where('id',$id)->update(
            $data
        );
        
    }

    public function deleteBlogById(Request $request)
    {

        $data=$request->all();
        $results=Blogs::where('id',$data[0])->delete();
        return $this->respond('Blog Deleted');
        
    }
    
    
    /**
     * Approves the Business of Customer
     * 
     * @param  Request    $request
     * @return Response
     */
    
}