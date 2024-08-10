namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

class LocationFinderForm extends FormBase {

  public function getFormId() {
    return 'dhl_location_finder_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
    ];
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
    ];
    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Locations'),
    ];

    if ($locations = $form_state->getValue('locations')) {
      $form['locations'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Filtered Locations (YAML)'),
        '#default_value' => $locations,
        '#rows' => 20,
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country = $form_state->getValue('country');
    $city = $form_state->getValue('city');
    $postal_code = $form_state->getValue('postal_code');

    $client = new Client();
    $response = $client->request('GET', 'https://api.dhl.com/location-finder', [
      'headers' => [
        'DHL-API-Key' => 'demo-key',
      ],
      'query' => [
        'countryCode' => $country,
        'addressLocality' => $city,
        'postalCode' => $postal_code,
      ],
    ]);

    $data = json_decode($response->getBody(), TRUE);
    $filtered_locations = $this->filterLocations($data['locations']);
    $yaml_output = Yaml::dump($filtered_locations, 4, 2);
    
    $form_state->setValue('locations', $yaml_output);
    $form_state->setRebuild(TRUE);
  }

  private function filterLocations(array $locations) {
    $filtered = array_filter($locations, function($location) {
      $address = $location['address']['streetAddress'];
      $weekends = ['saturday', 'sunday'];
      $open_on_weekends = array_reduce($weekends, function($carry, $day) use ($location) {
        return $carry && !empty($location['openingHours'][$day]);
      }, TRUE);

      if (!$open_on_weekends) {
        return FALSE;
      }

      $address_number = (int) filter_var($address, FILTER_SANITIZE_NUMBER_INT);
      if ($address_number % 2 !== 0) {
        return FALSE;
      }

      return TRUE;
    });

    return array_map(function($location) {
      return [
        'locationName' => $location['locationName'],
        'address' => $location['address'],
        'openingHours' => $location['openingHours'],
      ];
    }, $filtered);
  }
}
