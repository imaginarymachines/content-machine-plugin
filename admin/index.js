import React from 'react';
import { render } from 'react-dom';

import {
	Form,
	FormTable,
	FormProps,
	TrInput,
	TrSelect,
	TrSubmitButton
  }
  from  "@imaginary-machines/wp-admin-components";
import apiFetch from '@wordpress/api-fetch';

//Function for saving settings
const  saveSettings = async (values) => {
	const r = await apiFetch( {
		path: '/content-machine/v1/settings',
		method: 'POST',
		data: values,
	} ).then( ( res ) => {
		return res;
	} );
	return {update:r};
}


const SettingsForm = () => {
	const [isSaving,setIsSaving] = React.useState(false);
	const [hasSaved,setHasSaved] = React.useState(false);
	const [values,setValues] = React.useState(() => {
		if( CONTENT_MACHINE.settings ){
			return CONTENT_MACHINE.settings;
		}
		return {
			key:'',
			url: '',
		};
	});
	const id = "settings-form";
	const onSubmit = (e) => {
		e.preventDefault();
		setIsSaving(true);
		saveSettings(values).then(({update}) => {
			setValues({...values,update});
			setHasSaved(true);
		});
	}

	//Reset the isSaving state after 2 seconds
	React.useEffect(() => {
		if( hasSaved ){
			const timer = setTimeout(() => {
				setIsSaving(false);
			}, 2000);
			return () => clearTimeout(timer);
		}

	  }, [hasSaved]);
	return (
		<Form id={id} onSubmit={onSubmit}>
		  <FormTable >
			  <>
				  <TrInput
					  label={'Api Key'}
					  id={'input'}
					  name={'key'}
					  value={values.key}
					  onChange={(value) => setValues({...values,key:value})}
				  />
				  <TrSelect
					  label={'Select Field'}
					  id={'select'}
					  name={'select'}
					  value={values.select}
					  options={[
						{

							label:'One',
							value:'one'
						},
						{
							label:'Two',
							value:'two'
						},
					  ]}
					  onChange={(value) => setValues({...values,select:value})}
				  />
				  <TrSubmitButton
					  id={'submit-button'}
					  name={'submit-button'}
					  value={'Save'}
				  />
				  <>{isSaving ? "Saving..." : ""}</>
			  </>
		  </FormTable>
	  </Form>
	)
  }
const App = () => {
	  return <SettingsForm />
}

render(<App />, document.getElementById('content-machine-settings'));