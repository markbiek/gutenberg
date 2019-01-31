
import UIKit
import RNReactNativeGutenbergBridge
import Aztec

class GutenbergViewController: UIViewController {

    fileprivate lazy var gutenberg = Gutenberg(dataSource: self)
    fileprivate var htmlMode = false
    fileprivate var mediaPickCoordinator: MediaPickCoordinator?
    fileprivate lazy var mediaUploadCoordinator: MediaUploadCoordinator = {
        let mediaUploadCoordinator = MediaUploadCoordinator(gutenberg: self.gutenberg)
        return mediaUploadCoordinator
    }()
    
    override func loadView() {
        view = gutenberg.rootView
    }

    override func viewDidLoad() {
        super.viewDidLoad()
        configureNavigationBar()
        gutenberg.delegate = self
        navigationController?.navigationBar.isTranslucent = false
    }

    @objc func moreButtonPressed(sender: UIBarButtonItem) {
        showMoreSheet()
    }

    @objc func saveButtonPressed(sender: UIBarButtonItem) {
        gutenberg.requestHTML()
    }
}

extension GutenbergViewController: GutenbergBridgeDelegate {

    func gutenbergDidLoad() {
        
    }

    func gutenbergDidProvideHTML(title: String, html: String, changed: Bool) {
        print("didProvideHTML:")
        print("↳ Content changed: \(changed)")
        print("↳ Title: \(title)")
        print("↳ HTML: \(html)")
    }

    func gutenbergDidRequestMedia(from source: MediaPickerSource, with callback: @escaping MediaPickerDidPickMediaCallback) {
        switch source {
        case .mediaLibrary:
            print("Gutenberg did request media picker, passing a sample url in callback")
            callback(1, "https://cldup.com/cXyG__fTLN.jpg")
        case .deviceLibrary:
            print("Gutenberg did request a device media picker, opening the device picker")
            pickAndUpload(from: .savedPhotosAlbum, callback: callback)
        case .deviceCamera:
            print("Gutenberg did request a device media picker, opening the camera picker")
            pickAndUpload(from: .camera, callback: callback)
        }
    }

    func pickAndUpload(from source: UIImagePickerController.SourceType, callback: @escaping MediaPickerDidPickMediaCallback) {
        mediaPickCoordinator = MediaPickCoordinator(presenter: self, callback: { (url) in
            guard let url = url, let mediaID = self.mediaUploadCoordinator.upload(url: url) else {
                callback(nil, nil)
                return
            }
            callback(mediaID, url.absoluteString)
            self.mediaPickCoordinator = nil
        } )
        mediaPickCoordinator?.pick(from: source)
    }

    func gutenbergDidRequestMediaUploadSync() {
        print("Gutenberg request for media uploads to be resync")
    }

    func gutenbergDidRequestMediaUploadActionDialog(for mediaID: Int) {
        
    }
}

extension GutenbergViewController: GutenbergBridgeDataSource {
    func gutenbergInitialContent() -> String? {
        return nil
    }
    
    func gutenbergInitialTitle() -> String? {
        return nil
    }

    func aztecAttachmentDelegate() -> TextViewAttachmentDelegate {
        return ExampleAttachmentDelegate()
    }
}

//MARK: - Navigation bar

extension GutenbergViewController {

    func configureNavigationBar() {
        addSaveButton()
        addMoreButton()
    }

    func addSaveButton() {
        navigationItem.leftBarButtonItem = UIBarButtonItem(barButtonSystemItem: .save,
                                                           target: self,
                                                           action: #selector(saveButtonPressed(sender:)))
    }

    func addMoreButton() {
        navigationItem.rightBarButtonItem = UIBarButtonItem(title: "...",
                                                            style: .plain,
                                                            target: self,
                                                            action: #selector(moreButtonPressed(sender:)))
    }
}

//MARK: - More actions

extension GutenbergViewController {

    func showMoreSheet() {
        let alert = UIAlertController(title: nil, message: nil, preferredStyle: .actionSheet)
        
        let cancelAction = UIAlertAction(title: "Keep Editing", style: .cancel)
        alert.addAction(toggleHTMLModeAction)
        alert.addAction(updateHtmlAction)
        alert.addAction(cancelAction)

        present(alert, animated: true)
    }
    
    var toggleHTMLModeAction: UIAlertAction {
        return UIAlertAction(
            title: htmlMode ? "Switch To Visual" : "Switch to HTML",
            style: .default,
            handler: { [unowned self] action in
                self.toggleHTMLMode(action)
        })
    }
    
    var updateHtmlAction: UIAlertAction {
        return UIAlertAction(
            title: "Update HTML",
            style: .default,
            handler: { [unowned self] action in
                let alert = self.alertWithTextInput(using: { [unowned self] (htmlInput) in
                    if let input = htmlInput {
                        self.gutenberg.updateHtml(input)
                    }
                })
                self.present(alert, animated: true, completion: nil)
        })
    }
    
    func alertWithTextInput(using handler: ((String?) -> Void)?) -> UIAlertController {
        let alert = UIAlertController(title: "Enter HTML", message: nil, preferredStyle: .alert)
        alert.addTextField()
        let submitAction = UIAlertAction(title: "Submit", style: .default) { [unowned alert] (action) in
            handler?(alert.textFields?.first?.text)
        }
        alert.addAction(submitAction)
        alert.addAction(UIAlertAction(title: "Cancel", style: .cancel))
        return alert
    }
    
    func toggleHTMLMode(_ action: UIAlertAction) {
        htmlMode = !htmlMode
        gutenberg.toggleHTMLMode()
    }
}
